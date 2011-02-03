<!DOCTYPE html>
<html>
<head>

	<base href="<?php echo $this->config->item('base_url'); ?>" />
	<title>ExtID Test</title>
	
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
		
		pre {
		 padding: 15px;
		 background-color: #ddd;
		 border: 1px #888 solid;
		 font-family: Monaco, Verdana, Sans-serif;
		 font-size: 12px;
		 color: #002166;
		}
		
		.error {
		 padding: 15px;
		 background-color: #fdc;
		 border: 1px #987 solid;
		 font-family: Monaco, Verdana, Sans-serif;
		 font-size: 12px;
		 color: #431;
		 margin: 15px 25px;
		}
	</style>
	
	<?php
		if (! isset($result) && ! isset($error))
		{
			$this->extid->import_styles($extid_config);
		}
	?>

</head>
<body>

	<h1>Welcome to the test of the External Authentication library for CodeIgniter!</h1>
	
	<div id="wrapper">

		<?php
			
			if (isset($result))
			{
				// Dump the result
				echo '<p>The provider sent the following information:</p>';
				echo '<pre>$result = '.print_r($result, true).'</pre><a href="auth">Back</a>';
			}
			elseif (isset($error))
			{
				// Output the error
				echo '<p>An error occured:</p>';
				echo '<div class="error">'.$error.'</div><a href="auth">Back</a>';
			}
			else
			{
				// Insert the login code
				echo '<p>Please select one of the below login options to start the test</p>';
				echo $this->extid->generate_login($extid_config, 3);
			}

		?>
		
		<p><br /><a href="auth/clear">Clear Session</a></p>
		<p><br />Page rendered in {elapsed_time} seconds</p>
	
	</div>

	<!-- Load Scripts -->
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script type="text/javascript">
	<!--
		
		$('.extid-item.blogger, .extid-item.openid').each(function() {
			$('form', this).hide();
			$('a', this).click(function(e) {
				$('form', this.parentNode).slideToggle();
				e.preventDefault();
				return false;
			});
		});
		
	//-->
	</script>

</body>
</html>
