<?php  if ( ! empty($messages)) { ?>
<ul class="xm_message">
<?php
	foreach ($messages as $message) {
		echo '<li class="' . $level_to_class[$message['level']] . '">' . $message['message'] . '</li>' . EOL;
	} // foreach
?>
</ul>
<?php } // if ?>
