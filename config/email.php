<?php
/**
 * FGCK Joyland Email Configuration
 *
 * For Gmail:
 *   EMAIL_SMTP_HOST = 'smtp.gmail.com'
 *   EMAIL_SMTP_PORT = 587
 *   EMAIL_SMTP_ENCRYPTION = 'tls'
 *   EMAIL_SMTP_USERNAME = your Gmail address
 *   EMAIL_SMTP_PASSWORD = your Google App Password (NOT your normal password)
 *
 * For a hosting provider, use the SMTP details supplied by the host.
 */
const EMAIL_ENABLED = true;
const EMAIL_SMTP_HOST = 'smtp.gmail.com';
const EMAIL_SMTP_PORT = 587;
const EMAIL_SMTP_ENCRYPTION = 'tls'; // tls for 587, ssl for 465
const EMAIL_SMTP_USERNAME = 'fgckjoyland@gmail.com';
const EMAIL_SMTP_PASSWORD = 'YOUR_16_CHARACTER_APP_PASSWORD';
const EMAIL_FROM_EMAIL = 'fgckjoyland@gmail.com';
const EMAIL_FROM_NAME = 'FGCK Joyland Appointment System';
const EMAIL_REPLY_TO = 'fgckjoyland@gmail.com';
const EMAIL_BASE_URL = 'http://localhost/fgck_joyland';
