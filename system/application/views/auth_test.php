<html>
<head>

	<base href="<?php echo $this->config->item('base_url'); ?>" />
	<title>OAuth Test</title>
	
	<style type="text/css">
		* {
		 font-family: Lucida Grande, Verdana, Sans-serif;
		 color: #4F5155;
		}
		
		body {
		 background-color: #fff;
		 margin: 40px;
		 font-size: 14px;
		}

		a {
		 color: #003399;
		 background-color: transparent;
		 font-weight: normal;
		}

		h1 {
		 color: #444;
		 background-color: transparent;
		 border-bottom: 1px solid #D0D0D0;
		 font-size: 16px;
		 font-weight: bold;
		 margin: 24px 0 2px 0;
		 padding: 5px 0 6px 0;
		}

		code {
		 font-family: Monaco, Verdana, Sans-serif;
		 font-size: 12px;
		 background-color: #f9f9f9;
		 border: 1px solid #D0D0D0;
		 color: #002166;
		 display: block;
		 margin: 14px 0 14px 0;
		 padding: 12px 10px 12px 10px;
		}
	</style>

</head>
<body>

	<h1>Welcome to the test of the External Authentication library for CodeIgniter!</h1>
	
	<div id="wrapper">
	
		<p>Please select one of the below login options to start the test</p>

		<?php
			
			// Insert the login code
			echo $login_code;

		?>

		<p><br />Page rendered in {elapsed_time} seconds</p>
	
	</div>

	<!-- Load Scripts -->
	<script type="text/javascript" src="http://static.kbjrweb.com/scripts/jquery.js"></script>
	<script type="text/javascript" src="http://static.kbjrweb.com/scripts/placeholder.js"></script>
	<script type="text/javascript"><!--
	$(function() {
		
		$('.openid span, .blogger span').each(function() {
		
			
		
		});
		
	});
	//--></script>

</body>
</html>
