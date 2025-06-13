<?php
class Account {
    private $conn;
    private $table_name = "accounts";

    public $id;
    public $user_id;
    public $account_number;
    public $balance;
    public $account_type;
    public $created_at;
    public $account_holder;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    account_number = :account_number,
                    balance = :balance,
                    account_type = :account_type,
                    account_holder = :account_holder,
                    created_at = :created_at";

        $stmt = $this->conn->prepare($query);

        $this->account_number = $this->generateAccountNumber();
        $this->balance = 0;
        $this->created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":account_number", $this->account_number);
        $stmt->bindParam(":balance", $this->balance);
        $stmt->bindParam(":account_type", $this->account_type);
        $stmt->bindParam(":account_holder", $this->account_holder);
        $stmt->bindParam(":created_at", $this->created_at);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    private function generateAccountNumber() {
        return date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getAccountByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccountByNumber($account_number) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE account_number = :account_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_number", $account_number);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateBalance($amount) {
        $query = "UPDATE " . $this->table_name . "
                SET balance = balance + :amount
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function deleteAccount($account_id) {
        // First, delete all transactions for this account
        $query1 = "DELETE FROM transactions WHERE account_id = :id";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->bindParam(":id", $account_id, PDO::PARAM_INT);
        $stmt1->execute();
        // Then, delete the account
        $query2 = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bindParam(":id", $account_id, PDO::PARAM_INT);
        return $stmt2->execute();
    }
}
?> 