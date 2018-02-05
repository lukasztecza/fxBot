<?php
namespace TinyApp\Model\Repository;

class PriceRepository extends RepositoryAbstract
{
    public function savePrices(array $prices) : array
    {
        $this->getWrite()->begin();
        try {
            $this->getWrite()->prepare(
                'INSERT INTO `price` (`pack`, `instrument`, `datetime`, `open`, `high`, `low`, `close`)
                VALUES (:pack, :instrument, :datetime, :open1, :high1, :low1, :close1)
                ON DUPLICATE KEY UPDATE `open` = :open2, `high` = :high2, `low` = :low2, `close` = :close2' 
            );
            $affectedIds = [];
            foreach ($prices as $price) {
                $affectedIds[] = $this->getWrite()->execute(null, [
                    'pack' => $price['pack'],
                    'instrument' => $price['instrument'],
                    'datetime' => $price['datetime'],
                    'open1' => $price['open'],
                    'high1' => $price['high'],
                    'low1' => $price['low'],
                    'close1' => $price['close'],
                    'open2' => $price['open'],
                    'high2' => $price['high'],
                    'low2' => $price['low'],
                    'close2' => $price['close']
                ]);
            }
            $this->getWrite()->commit();
        } catch(\Throwable $e) {
            $this->getWrite()->rollBack();
            trigger_error(
                'Rolling back after failed attempt to save prices with message ' . $e->getMessage() . ' with payload ' . var_export($prices, true)
            );
            throw $e;
        }

        return $affectedIds;
    }

    public function getLatestPriceByInstrumentAndPack(string $instrument, string $pack) : array
    {
        $records = $this->getRead()->fetch(
            'SELECT * FROM `price` WHERE `instrument` = :instrument AND `pack` = :pack ORDER BY `datetime` DESC LIMIT 1',
            ['instrument' => $instrument, 'pack' => $pack]
        );

        return !empty($records) ? array_pop($records) : [];
    }

    public function getInitialPrices(array $priceInstruments, string $initialDateTime) : array
    {
        $this->getRead()->prepare(
            'SELECT * FROM `price` WHERE `instrument` = :instrument AND `pack` = "real" AND `datetime` >= :datetime ORDER BY `datetime` ASC LIMIT 1'
        );
        $initialPrices = [];
        foreach ($priceInstruments as $priceInstrument) {
            $records = $this->getRead()->fetch(null, [
                'instrument' => $priceInstrument,
                'datetime' => $initialDateTime
            ]);
            $initialPrices[] = !empty($records) ? array_pop($records) : [];
        }

        return $initialPrices;
    }
}
