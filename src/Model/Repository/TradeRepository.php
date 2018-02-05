<?php
namespace TinyApp\Model\Repository;

class TradeRepository extends RepositoryAbstract
{
    public function saveTrade(array $trade) : int
    {
        $affectedId = $this->getWrite()->execute(
            'INSERT INTO `trade` (`pack`, `instrument`, `units`, `price`, `take_profit`, `stop_loss`, `balance`, `datetime`)
            VALUES (:pack, :instrument, :units, :price, :takeProfit, :stopLoss, :balance, :datetime)', [
                'pack' => $trade['pack'],
                'instrument' => $trade['instrument'],
                'units' => $trade['units'],
                'price' => $trade['price'],
                'takeProfit' => $trade['takeProfit'],
                'stopLoss' => $trade['stopLoss'],
                'balance' => $trade['balance'],
                'datetime' => $trade['datetime']
            ]
        );

        return (int)$affectedId;
    }
}
