<?php
  class TemporaryAuthorizationLevel {
    public $id;
    public $person_id;
    public $auth_level;
    public $created_at;
    public $expires_at;

    public function __construct($id, $person_id, $auth_level, $created_at, $expires_at) {
      $this->id = $id;
      $this->person_id = $person_id;
      $this->auth_level = $auth_level;
      $this->created_at = $created_at;
      $this->expires_at = $expires_at;
    }

    public static function from_assoc_row($row) {
      return new TemporaryAuthorizationLevel($row['id'], $row['person_id'], $row['auth_level'], strtotime($row['created_at']), strtotime($row['expires_at']));
    }
  }

  class TemporaryAuthorizationLevelMapping {
    public static function fetch_by_person_id($sql_conn, $person_id) {
      $err_prefix = "TemporaryAuthorizationLevelMapping#fetch_by_person_id";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("SELECT * FROM temporary_auth_levels WHERE person_id=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $person_id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      check_db_error($sql_conn, $err_prefix, $res = $stmt->get_result());

      $level = null;
      $row = $res->fetch_assoc();
      if($row) {
	$level = TemporaryAuthorizationLevel::from_assoc_row($row);
      }
      $res->close();
      $stmt->close();
      return $level;
    }

    public static function delete_by_id($sql_conn, $id) {
      $err_prefix = "TemporaryAuthorizationLevelMapping#delete_by_id";
      check_db_error($sql_conn, $err_prefix, $stmt = $sql_conn->prepare("DELETE FROM temporary_auth_levels WHERE id=?"));
      check_db_error($sql_conn, $err_prefix, $stmt->bind_param("i", $id));
      check_db_error($sql_conn, $err_prefix, $stmt->execute());
      $stmt->close();
    }
  }
?>
