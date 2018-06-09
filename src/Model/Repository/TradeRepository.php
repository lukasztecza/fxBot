<?php
namespace FxBot\Model\Repository;

use LightApp\Model\Repository\RepositoryAbstract;

class TradeRepository extends RepositoryAbstract
{
    public function saveTrade(array $trade) : int
    {
        $affectedId = $this->getWrite()->execute(
            'INSERT INTO `trade` (`account`, `instrument`, `units`, `price`, `take_profit`, `stop_loss`, `balance`, `datetime`)
            VALUES (:account, :instrument, :units, :price, :takeProfit, :stopLoss, :balance, :datetime)', [
                'account' => $trade['account'],
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

    public function getTrades(string $account, int $page, int $perPage) : array
    {
         $trades = $this->getRead()->fetch(
             'SELECT `id`, `account`, `instrument`, `units`, `price`, `take_profit` takeProfit, `stop_loss` stopLoss, `balance`, `datetime`
              FROM `trade`
              WHERE `account` = :account
              LIMIT ' . ($page - 1) * $perPage . ', ' . $perPage,
              ['account' => $account]
         );
         $pages = $this->getPages(
            'SELECT COUNT(id) AS count FROM `trade` WHERE `account` = :account', ['account' => $account], $perPage
         );

         return ['trades' => $trades, 'page' => $page, 'pages' => $pages];
    }
}
