<?php
  require_once 'database/common.php';

  class Person {
    public $id;
    public $username;
    public $password_hash;
    public $email;
    public $auth_level;
    public $created_at;
    public $updated_at;

    public function __construct($id, $username, $password_hash, $email, $auth_level, $created_at, $updated_at) {
      $this->id = $id;
      $this->username = $username;
      $this->password_hash = $password_hash;
      $this->email = $email;
      $this->auth_level = $auth_level;
      $this->created_at = $created_at;
      $this->updated_at = $updated_at;
    }

    public static function from_assoc_row($row) {
      return new Person($row['id'], $row['username'], $row['password_hash'], $row['email'], $row['auth'], strtotime($row['created_at']), strtotime($row['updated_at']));
    }
  }

  class PersonMapping {
    public static function fetch_by_id($sql_conn, $id) {
      $err_prefix = "PersonMapping#fetch_by_id";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM persons WHERE id=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $person = null;
      $row = $res->fetch_assoc();
      if($row) {
	$person = Person::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $person;
    }

    public static function fetch_by_username($sql_conn, $username) {
      $err_prefix = "PersonMapping#fetch_by_username";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM persons WHERE username=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('s', $username));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $person = null;
      $row = $res->fetch_assoc();
      if($row) {
	$person = Person::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $person;
    }

    public static function update_row($sql_conn, $person) {
      $err_prefix = "PersonMapping#update_row";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("UPDATE persons SET username=?, password_hash=?, email=?, auth=?, created_at=?, updated_at=? WHERE id=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('sssissi', $person->username, $person->password_hash, $person->email, $person->auth_level,
					     date('Y-m-d H:i:s', $person->created_at), date('Y-m-d H:i:s', $person->updated_at), $person->id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());

      $stmt->close();
    }

    public static function insert_row($sql_conn, $person) {
      $err_prefix = "PersonMapping#insert_row";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("INSERT INTO persons (username, password_hash, email, auth, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param('sssiss', $person->username, $person->password_hash, $person->email, $person->auth_level,
					     date('Y-m-d H:i:s', $person->created_at), date('Y-m-d H:i:s', $person->updated_at)));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());

      $person->id = $sql_conn->insert_id;
      $stmt->close();
    }
  }
?>
