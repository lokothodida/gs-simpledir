<?php
// Admin panel functions
function simpledir_admin_message($status, $message) {
  ?>
  <script type="text/javascript">
    $(function() {
      var msg = <?php echo json_encode($message); ?>;
      $("div.bodycontent").before(
        "<div class=\"updated\" style=\"display:block;\">" + msg + "</div>");
      $(".updated, .error").fadeOut(500).fadeIn(500);
    });
  </script>
  <?php
}