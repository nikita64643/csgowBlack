<?php

class GDonateModel
{
    private $mysqli;

    static function getInstance()
    {
        return new self();
    }

    private function __construct()
    {
        $this->mysqli = @new mysqli (
            Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME
        );
        /* проверка подключения */
        if (mysqli_connect_errno()) {
            throw new Exception('Не удалось подключиться к бд');
        }
    }

    function createPayment($gdonateId, $account, $sum, $itemsCount)
    {
        $query = '
            INSERT INTO
                gdonate_payments (gdonateId, account, sum, itemsCount, dateCreate, status)
            VALUES
                (
                    "'.$this->mysqli->real_escape_string($gdonateId).'",
                    "'.$this->mysqli->real_escape_string($account).'",
                    "'.$this->mysqli->real_escape_string($sum).'",
                    "'.$this->mysqli->real_escape_string($itemsCount).'",
                    NOW(),
                    0
                )
        ';

        return $this->mysqli->query($query);
    }

    function getPaymentByGDonateId($gdonateId)
    {
        $query = '
                SELECT * FROM
                    gdonate_payments
                WHERE
                    gdonateId = "'.$this->mysqli->real_escape_string($gdonateId).'"
                LIMIT 1
            ';
            
        $result = $this->mysqli->query($query);
        return $result->fetch_object();
    }

    function confirmPaymentByGDonateId($gdonateId)
    {
        $query = '
                UPDATE
                    gdonate_payments
                SET
                    status = 1,
                    dateComplete = NOW()
                WHERE
                    gdonateId = "'.$this->mysqli->real_escape_string($gdonateId).'"
                LIMIT 1
            ';
        return $this->mysqli->query($query);
    }
    
    function getAccountByName($account)
    {
        $sql = "
            SELECT
                *
            FROM
               ".Config::TABLE_ACCOUNT."
            WHERE
               ".Config::TABLE_ACCOUNT_NAME." = '".$this->mysqli->real_escape_string($account)."'
            LIMIT 1
         ";
         
        $result = $this->mysqli
            ->query($sql);
            
        return $result->fetch_object();
    }
    
    function donateForAccount($account, $count)
    {
        $query = "
            UPDATE
                ".Config::TABLE_ACCOUNT."
            SET
                ".Config::TABLE_ACCOUNT_DONATE." = ".Config::TABLE_ACCOUNT_DONATE." + ".$this->mysqli->real_escape_string($count)."
            WHERE
                ".Config::TABLE_ACCOUNT_NAME." = '".$this->mysqli->real_escape_string($account)."'
        ";
        
        return $this->mysqli->query($query);
    }
}