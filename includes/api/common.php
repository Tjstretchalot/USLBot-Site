<?php
  function echo_fail($resp_code, $err_type, $err_mess) {
    http_response_code($resp_code);

    echo(json_encode(array(
      'success' => false,
      'error_type' => $err_type,
      'error_message' => $err_mess
    )));
  }

  function echo_success($data) {
    http_response_code(200);

    echo(json_encode(array(
      'success' => true,
      'data' => $data
    )));
  }
?>
