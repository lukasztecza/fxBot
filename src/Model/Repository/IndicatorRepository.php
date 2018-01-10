<?php
namespace TinyApp\Model\Repository;

class IndicatorRepository extends RepositoryAbstract
{
    public function savePrices(array $prices) : array
    {
        $this->getWrite()->begin();
        try {
            $this->getWrite()->prepare(
                'INSERT INTO `indicator` (`pack`, `instrument`, `datetime`, `open`, `high`, `low`, `average`, `close`, `extrema`)
                VALUES (:pack, :instrument, :datetime, :open, :high, :low, :average, :close, :extrema)'
            );
            $affectedIds = [];
            foreach ($prices as $price) {
                $affectedIds[] = $this->getWrite()->execute(null, [
                    'pack' => $price['pack'],
                    'instrument' => $price['instrument'],
                    'datetime' => $price['datetime'],
                    'open' => $price['open'],
                    'high' => $price['high'],
                    'low' => $price['low'],
                    'average' => $price['average'],
                    'close' => $price['close'],
                    'extrema' => $price['extrema']
                ]);
            }
        } catch(\Throwable $e) {
            $this->getWrite()->rollBack();
            trigger_error(
                'Rolling back after failed attempt to save prices with message ' . $e->getMessage() . ' with payload ' . var_export($prices, true)
            );
            throw $e;
        }
        $this->getWrite()->commit();

        return $affectedIds;
    }

    public function getLatestPriceByInstrumentAndPack(string $instrument, string $pack) : array
    {
        $records = $this->getRead()->fetch(
            'SELECT * FROM `price` WHERE `instrument` = :instrument AND `pack` = :pack ORDER BY `id` DESC LIMIT 1',
            ['instrument' => $instrument, 'pack' => $pack]
        );

        return !empty($records) ? array_pop($records) : [];
    }

    public function deletePriceById(int $id) : bool
    {
        $this->getWrite()->execute(
            'DELETE FROM `price` WHERE `id` = :id', ['id' => $id]
        );

        return true;
    }
}
