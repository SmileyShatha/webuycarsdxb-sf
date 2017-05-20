<?php

namespace Wbc\ValuationBundle;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Wbc\ValuationBundle\Entity\TrainingData;
use Wbc\ValuationBundle\Entity\Valuation;

/**
 * Class ValuationManager.
 *
 * @author Majid Mvulle <majid@majidmvulle.com>
 *
 * @DI\Service("wbc.valuation_manager")
 */
class ValuationManager
{
    const MIN_ALLOWABLE_PRICE = 500;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $valuationCommand;

    /**
     * @var int
     */
    private $valuationDiscountPercentage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ValuationManager Constructor.
     *
     * @DI\InjectParams({
     * "entityManager" = @DI\Inject("doctrine.orm.default_entity_manager"),
     * "valuationCommand" = @DI\Inject("%valuation_command%"),
     * "valuationDiscountPercentage" = @DI\Inject("%valuation_discount_percentage%"),
     * "logger" = @DI\Inject("logger")
     * })
     *
     * @param EntityManager   $entityManager
     * @param string          $valuationCommand
     * @param int             $valuationDiscountPercentage
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, $valuationCommand, $valuationDiscountPercentage, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->valuationCommand = $valuationCommand;
        $this->valuationDiscountPercentage = $valuationDiscountPercentage;
        $this->logger = $logger;
    }

    public function setPrice(Valuation $valuation)
    {
        $filePath = $this->generateTrainingDataFile($valuation);
        $output = null;

        //No Training Data available, just bounce
        if (!$filePath) {
            return;
        }

        $command = strtr($this->valuationCommand, ['%filePath%' => escapeshellarg($filePath)]);

        try {
            $process = new Process($command);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $output = $process->getOutput();
        } catch (ProcessFailedException $e) {
            $this->logger->critical($e->getMessage());
        }

        if ($output) {
            $output = json_decode($output, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($output['price'])) {
                $price = $this->roundUpToAny(intval($output['price']));

                if ($price && $price > self::MIN_ALLOWABLE_PRICE) {
                    $discounts = $this->getValuationConfigurationDiscounts($valuation);

                    foreach ($discounts as $discount) {
                        $price = $price + $price * intval($discount['discount']) / 100;
                    }

                    $price = $price + $price * $this->valuationDiscountPercentage / 100;
                    $valuation->setPriceOnline($price);
                    $this->entityManager->flush();
                }
            }
        }
    }

    protected function generateTrainingDataFile(Valuation $valuation)
    {
        $modelId = $valuation->getVehicleModel()->getId();
        $makeId = $valuation->getVehicleMake()->getId();
        $year = $valuation->getVehicleYear();
        $mileage = $valuation->getVehicleMileage();

        $color = strtolower($valuation->getVehicleColor());

        if (isset(TrainingData::$colors[$color])) {
            $color = TrainingData::$colors[$color];
        } else {
            $color = TrainingData::$colors['other'];
        }

        $bodyCondition = strtolower($valuation->getVehicleBodyCondition());

        if (isset(TrainingData::$bodyConditions[$bodyCondition])) {
            $bodyCondition = TrainingData::$bodyConditions[$bodyCondition];
        } else {
            $bodyCondition = TrainingData::$bodyConditions['other'];
        }

        $testData = [
            'a_make' => intval($makeId),
            'b_model' => intval($modelId),
            'c_year' => intval($year),
            'd_mileage' => intval($mileage),
            'f_color' => intval($color),
            'g_body_condition' => intval($bodyCondition),
            'z_price' => 0,
        ];

        $connection = $this->entityManager->getConnection();

        $statement = $connection->prepare('SELECT
                                                CAST(make_id AS UNSIGNED) AS a_make,
                                                CAST(model_id AS UNSIGNED) AS b_model,
                                                CAST(year AS UNSIGNED) AS c_year,
                                                CAST(mileage AS UNSIGNED) AS d_mileage,
                                                CAST(color AS UNSIGNED) AS f_color,
                                                CAST(body_condition AS UNSIGNED) AS g_body_condition,
                                                CAST(price AS UNSIGNED) AS z_price
                                            FROM valuation_training_data
                                            WHERE
                                                year BETWEEN :yearMin AND :yearMax
                                            AND mileage BETWEEN :mileageMin AND :mileageMax
                                            AND model_id = :modelId');

        $statement->bindValue(':yearMin', $year - 1, \PDO::PARAM_INT);
        $statement->bindValue(':yearMax', $year + 1, \PDO::PARAM_INT);
        $statement->bindValue(':mileageMin', $mileage - 10000, \PDO::PARAM_INT);
        $statement->bindValue(':mileageMax', $mileage + 10000, \PDO::PARAM_INT);
        $statement->bindValue(':modelId', $modelId, \PDO::PARAM_INT);
        $statement->execute();

        $trainingData = $statement->fetchAll();

        if (!$trainingData) {
            return;
        }

        array_walk($trainingData, function (&$item) {
            foreach ($item as $key => $_item) {
                $item[$key] = intval($_item);
            }
        });

        $trainingData[] = $testData;

        $fs = new Filesystem();
        $valuationDir = '/tmp/wbc-valuations/';

        if (!$fs->exists($valuationDir)) {
            $fs->mkdir($valuationDir);
        }

        $filePath = sprintf('%s%s.json', $valuationDir, $valuation->getId());

        $fs->dumpFile($filePath, json_encode($trainingData), 0777);

        return $filePath;
    }

    private function getValuationConfigurationDiscounts(Valuation $valuation)
    {
        $year = $valuation->getVehicleYear();
        $makeId = $valuation->getVehicleMake()->getId();
        $modelId = $valuation->getVehicleModel()->getId();

        $connection = $this->entityManager->getConnection();
        $statement = $connection->prepare('
                SELECT discount
                FROM valuation_configuration
                WHERE (vehicle_year = :year AND vehicle_make_id IS NULL AND vehicle_model_id IS NULL)
                OR (vehicle_make_id = :makeId AND vehicle_model_id IS NULL AND vehicle_year IS NULL)
                OR (vehicle_model_id = :modelId AND vehicle_year IS NULL)
                OR (vehicle_model_id = :modelId AND vehicle_year = :year)
        ');
        $statement->bindValue(':year', $year, \PDO::PARAM_INT);
        $statement->bindValue(':makeId', $makeId, \PDO::PARAM_INT);
        $statement->bindValue(':modelId', $modelId, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function roundUpToAny($n, $x = 5)
    {
        return (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
    }
}
