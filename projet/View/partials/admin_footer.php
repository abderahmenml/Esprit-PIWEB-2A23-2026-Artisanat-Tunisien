  </div><!-- /.content-area -->
</main><!-- /.main-content -->

<?php
$baseUrl = isset($baseUrl) ? $baseUrl : rtrim(APP_URL, '/');
if (preg_match('/\.html?$/i', $baseUrl)) {
  $baseUrl = rtrim(dirname($baseUrl), '/');
}
$adminJsUrl = $baseUrl . '/public/js/admin.js';
?>
<script src="<?= htmlspecialchars($adminJsUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
