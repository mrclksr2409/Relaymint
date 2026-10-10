<?php
/**
 * PHPMailer with an additional Microsoft Graph transport.
 *
 * Loaded on demand by Relaymint_Mailer::install_phpmailer() after the WordPress
 * PHPMailer classes, because it extends WP_PHPMailer.
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PHPMailer builds the MIME message as usual; for Graph connections the message is
 * handed to Microsoft Graph instead of an SMTP server.
 */
class Relaymint_PHPMailer extends WP_PHPMailer {

	/**
	 * Value of PHPMailer::$Mailer that selects the Graph transport (PHPMailer calls "{$Mailer}Send").
	 */
	const GRAPH = 'relaymintgraph';

	/**
	 * Resolved connection used by the Graph transport.
	 *
	 * @var array|null
	 */
	public $relaymint_connection = null;

	/**
	 * Send the message via Microsoft Graph.
	 *
	 * @param string $header MIME headers.
	 * @param string $body   MIME body.
	 * @return bool
	 * @throws PHPMailer\PHPMailer\Exception When Graph rejects the message.
	 */
	protected function relaymintgraphSend( $header, $body ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- name required by PHPMailer::postSend().
		if ( ! is_array( $this->relaymint_connection ) ) {
			throw new PHPMailer\PHPMailer\Exception( __( 'No Microsoft connection configured.', 'relaymint' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- plain text, escaped on output.
		}

		// PHPMailer leaves Bcc out of the headers for network transports; Graph reads recipients from the MIME headers.
		$bcc  = $this->getBccAddresses() ? $this->addrAppend( 'Bcc', $this->getBccAddresses() ) : '';
		$mime = rtrim( $header, "\r\n" ) . static::$LE . $bcc . static::$LE . $body; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$result = Relaymint_Microsoft::send( $this->relaymint_connection, $mime, $this->From ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( is_wp_error( $result ) ) {
			throw new PHPMailer\PHPMailer\Exception( $result->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- plain text, escaped on output.
		}
		return true;
	}
}
