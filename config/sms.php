<?php
/**
 * FGCK Makutano West Joyland SMS configuration.
 *
 * This implementation uses Twilio for SMS delivery.
 * Create a Twilio account, obtain an Account SID/Auth Token,
 * and a Twilio phone number capable of sending SMS.
 */
const SMS_ENABLED = false;
const SMS_TWILIO_ACCOUNT_SID = 'YOUR_TWILIO_ACCOUNT_SID';
const SMS_TWILIO_AUTH_TOKEN = 'YOUR_TWILIO_AUTH_TOKEN';
const SMS_TWILIO_FROM = 'YOUR_TWILIO_PHONE_NUMBER';
const SMS_TIMEOUT = 20;
