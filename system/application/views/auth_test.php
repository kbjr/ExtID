<html>
<head>

	<base href="<?php echo $this->config->item('base_url'); ?>" />
	<title>OAuth Test</title>

	<link rel="stylesheet" type="text/css" href="css/style.css" />

</head>
<body>

	<h1>Welcome to the test of the External Authentication library for CodeIgniter!</h1>
	
	<div id="wrapper">
	
		<p>Please select one of the below login options to start the test</p>

		<?php

			echo $login_code;

		?>

		<p><br />Page rendered in {elapsed_time} seconds</p>
	
	</div>

	<!-- Load Scripts -->
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/placeholder.js"></script>
	<script type="text/javascript" src="js/control.js"></script>

</body>
</html>
