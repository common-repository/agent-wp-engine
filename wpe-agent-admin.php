<style>

.log-display
{
	font-family: "Courier New", "Courier", fixed;
	font-size: 9pt;
}

.message
{
	text-align: center;
	vertical-align: middle;
	width: 400px;
	color: #000000;
	background: #c0ffc0;
	border: 1px solid #a0ffa0;
	margin: 0 0 0 3em;
	padding: 0.5ex 1em;
}

.form-explain
{
	display: inline;
	font-style: italic;
	font-size: 80%;
}

table.eventlog {
	
}

table.eventlog td,
table.eventlog th {
	text-align: left;
	vertical-align: baseline;
}

table.eventlog th {
	vertical-align: bottom;
}

.wpe-footer {
	font-size: smaller;
	text-align: right;
}

</style>

<?php

// Process submitting a form
if (isset($_POST['portal']) && $_POST['portal']) {
	check_admin_referer('wpe-admin-config');
	$this->set_option("account_id",strtolower($_POST['account_id']));
	$this->set_option("api_token",strtolower($_POST['api_token']));
	$this->set_option("sso_user_id",strtolower($_POST['sso_user_id']));
	$this->api_send_status();
}

?>

<div class="wrap">
	<h1><?php echo WpeAgent::PORTAL_NAME ?> &mdash; Agent Configuration</h1>

	<?php foreach ($this->errors as $html) { ?>
	<div class="error"><p><?php echo($html); ?></p></div>
	<?php } ?>
	
	<?php foreach ($this->messages as $html) { ?>
	<div class="updated fade"><p><?php echo($html); ?></p></div>
	<?php } ?>

	<form method="post" name="portal" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">

		<h2>Configuring Portal Access</h2>

		<p>
			Submit this form to list this blog on your
			<a href="<?php echo $this->portal_url ?>" target="_blank"><?php echo WpeAgent::PORTAL_NAME ?></a>.
		</p>

		<p class="submit submit-top">
			<?php wp_nonce_field('wpe-admin-config'); ?>
			<b>Account ID:</b>
				<input type="text" name="account_id" size="40" maxlength="40" value="<?php echo $this->get_option('account_id')?>">
				<span class="form-explain">from the footer of the portal</span>
				<br>
			<b>API Token:</b>
				<input type="text" name="api_token" size="40" maxlength="40" value="<?php echo $this->get_option('api_token')?>">
				<span class="form-explain">from the footer of the portal</span>
				<br>
			<b>SSO User:</b>
				<?php $this->emit_ui_select('sso_user_id',$this->get_users_for_select(),$this->get_option('sso_user_id')); ?>
				<span class="form-explain">When using Single Sign-On (SSO) from the Portal, log in automatically with this user.</span>
				<br>
			<input type="submit" name="portal" value="Save" class="button-primary"/>
		</p>
	</form>
	
<!--
	<hr/>
	<h2>WordPress Event Log</h2>
	<p>
		Here we log interesting events, also sending them to the <?php echo WpeAgent::PORTAL_NAME ?>.
	</p>
	<table class="eventlog">
		<tr>
			<th>Timestamp</th>
			<th>Severity</th>
			<th>Hook</th>
			<th>Description</th>
		</tr>
		<?php
			foreach ( $this->get_display_event_log() as $e ) {
				$html_tstamp = date('Y-m-d H:i:s',$e['tstamp']);
				$html_severity = htmlspecialchars($e['severity']);
				$html_hook = htmlspecialchars($e['hook']);
				$html_text = $e['html_text'];
		?>
		<tr>
			<td><?php echo $html_tstamp ?></td>
			<td><?php echo $html_severity ?></td>
			<td><?php echo $html_hook ?></td>
			<td><?php echo $html_text ?></td>
		</tr>
		<?php } ?>
	</table>
-->

	<hr/>
	<div class="wpe-footer">
		By <a href="http://wpengine.com" target="_blank">WP Engine</a> | v<?php $header_data = $this->get_header_data(); echo $header_data['Version'] ?>
	</div>
	
</div>
