<?php
namespace TinyApp\Model\Repository;

class TradeRepository extends RepositoryAbstract
{
    public function saveTrade(array $trade) : int
    {
        $affectedId = $this->getWrite()->execute(
            'INSERT INTO `trade` (`instrument`, `units`, `price`, `take_profit`, `stop_loss`, `datetime`)
            VALUES (:instrument, :units, :price, :takeProfit, :stopLoss, :datetime)', [
                'instrument' => $trade['instrument'],
                'units' => $trade['units'],
                'price' => $trade['price'],
                'takeProfit' => $trade['takeProfit'],
                'stopLoss' => $trade['stopLoss'],
                'datetime' => $trade['datetime']
            ]
        );

        return (int)$affectedId;
    }
}
