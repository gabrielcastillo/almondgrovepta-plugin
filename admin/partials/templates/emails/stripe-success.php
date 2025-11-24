<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta name="x-apple-disable-message-reformatting">
	<title><?php echo $this->site_name; ?> | <?php echo $this->page_title; ?></title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style>
        table, td, div, h1, p {font-family: Arial, sans-serif;}
	</style>
</head>
<body style="margin:0;padding:0;">
<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;background:#ffffff;">
	<tr>
		<td align="center" style="padding:0;">
			<table role="presentation" style="width:602px;border-collapse:collapse;border:1px solid #cccccc;border-spacing:0;text-align:left;">
				<tr>
					<td align="center" style="padding:5px 0px 5px 0px;background:#111827; border-bottom:1px solid #ededed; text-align:center; width:100%;">
						<p style="text-align:center;"><img src="<?php echo $this->site_url; ?>?logo=123456789" alt="<?php echo $this->site_url; ?>?logo=123456789" width="100" border="0" style="display:inline-block;" /></p>
					</td>
				</tr>
				<tr>
					<td style="padding:36px 30px 42px 30px;">
						<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
							<tr>
								<td style="padding:0 0 36px 0;color:#153643;">
									<h1 style="font-size:24px;margin:0 0 10px 0;font-family:Arial,sans-serif;">Your Purchase</h1>
									<p style="margin:0 0 12px 0;font-size:16px;line-height:24px;font-family:Arial,sans-serif;">
										Hello, <?php echo ucwords($this->customer_name); ?>
									</p>
								</td>
							</tr>
							<tr>
								<td style="padding:0;">
									<table role="presentation" style="width:602px;border-collapse:collapse;border:0;border-spacing:0;">
										<tr>
											<td style="padding:0 0 20px 0;vertical-align:top;color:#153643;" colspan="3">
												<?php echo nl2br($this->message_body); ?>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<!--                <tr>-->
				<!--                    <td>-->
				<!--                        <table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;font-size:12px;font-family:Arial,sans-serif;">-->
				<!--                            <tr>-->
				<!--                                <td align="center" style="padding:0;background-color:#f0f2f3;">-->
				<!--                                    <h3>Additional Portrait Options for the Senior!</h3>-->
				<!--                                    <p>Along with the traditional yearbook portrait we also offer the Traditional Digital Plus. Seniors, bring your lettermen's jacket, jersey, any prop you'd like included in your portrait (football, musical instruments...) or a favorite casual outfit. <br /><br /> TD School Portraits has many exciting digital backgrounds to select from. <br /> Please call TD School Portraits for further information on our new portrait session option for the Class of 2025 at 619-280-4299.</p>-->
				<!--                                </td>-->
				<!--                            </tr>-->
				<!--                        </table>-->
				<!--                    </td>-->
				<!--                </tr>-->
				<tr>
					<td style="padding:30px;background:#ffffff;border-top:1px solid #ededed;">
						<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;font-size:9px;font-family:Arial,sans-serif;">
							<tr>
								<td style="padding:0;color:#222222;" align="center">
									<p><?php echo $this->site_name; ?> | <a style="color:#222222;" href="<?php echo get_bloginfo('url'); ?>" target="_blank">Website</a> | <a style="color:#222222;" href="<?php echo $this->site_url . '/contact-us'; ?>" target="_blank">Contact Us</a></p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>