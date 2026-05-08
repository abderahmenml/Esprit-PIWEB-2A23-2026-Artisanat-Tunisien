<?php
session_start();
include '../../config.php';

$_SESSION = [];
if (session_id() !== '') {
    session_destroy();
}
?>
<script>
  sessionStorage.removeItem("cl_nom");
  sessionStorage.removeItem("cl_prenom");
  sessionStorage.removeItem("cl_email");
  sessionStorage.removeItem("cl_role");
  window.location.href = "homepage.html";
</script>
