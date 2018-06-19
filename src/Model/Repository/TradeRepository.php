<?php declare(strict_types=1);
namespace FxBot\Model\Repository;

use LightApp\Model\Repository\RepositoryAbstract;

class TradeRepository extends RepositoryAbstract
{
    public function saveTrade(array $trade) : int
    {
        try {
            $this->getWrite()->begin();
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

            $this->getWrite()->prepare(
                'INSERT INTO `parameter` (`name`) VALUES (:name)
                ON DUPLICATE KEY UPDATE `id` = `id`'
            );
            foreach ($trade['parameters'] as $name => $value) {
                $this->getWrite()->execute(null, [
                    'name' => $name
                ]);
            }

            $this->getWrite()->prepare(
                'INSERT INTO `trade_parameter` (`trade_id`, `parameter_id`, `value`)
                SELECT :tradeId, p.`id`, :value FROM `parameter` AS p WHERE p.`name` = :name'
            );
            foreach ($simulation['parameters'] as $name => $value) {
                $this->getWrite()->execute(null, [
                    'tradeId' => $affectedId,
                    'value' => $value,
                    'name' => $name
                ]);
            }
            $this->getWrite()->commit();
        } catch(\Throwable $e) {
            trigger_error(
                'Rolling back after failed attempt to save trade with message ' .
                $e->getMessage() . ' with payload ' . var_export($trade, true),
                E_USER_NOTICE
            );
            $this->getWrite()->rollBack();
            throw $e;
        }
        $this->getWrite()->clean();

        return (int) $affectedId;
    }

    public function updateTrade(string $tradeId, array $params) : int
    {
        $this->getWrite()->prepare(
            'INSERT INTO `parameter` (`name`) VALUES (:name)
            ON DUPLICATE KEY UPDATE `id` = `id`'
        );
        foreach ($params as $name => $value) {
            $this->getWrite()->execute(null, [
                'name' => $name
            ]);
        }

        $this->getWrite()->prepare(
            'INSERT INTO `trade_parameter` (`trade_id`, `parameter_id`, `value`)
            SELECT :tradeId, p.`id`, :value FROM `parameter` AS p WHERE p.`name` = :name'
        );
        foreach ($params as $name => $value) {
            $this->getWrite()->execute(null, [
                'tradeId' => $tradeId,
                'value' => $value,
                'name' => $name
            ]);
        }

        return (int) $affectedId;
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
