# Simulant AI - Email Responder GPT

## Overview
Simulant AI Email Responder is a PrestaShop module that automatically responds to customer emails using AI technology powered by OpenAI.

## Features
- Automatic email response generation
- AI-powered content creation
- Configurable response tone
- Multilingual support
- Logging and statistics tracking

## Requirements
- PrestaShop 1.7.x to 8.1.x
- PHP 7.4+
- CURL extension
- IMAP extension
- OpenAI API Key

## Installation
1. Upload the module to `/modules/bm_simulant_emailrespondergpt/`
2. Install the module through PrestaShop Back Office
3. Configure IMAP, SMTP, and OpenAI settings
4. Set up cron job for automated email processing

## Cron Job Configuration
Add the following to your crontab:
```
*/15 * * * * php /path/to/prestashop/modules/bm_simulant_emailrespondergpt/cron.php?token=YOUR_CRON_TOKEN
```

## Configuration Options
- Email response delay
- Response tone (professional, casual, friendly)
- Blocklist/Whitelist for email domains
- Debug logging
- Custom AI instructions

## Troubleshooting
- Ensure all required extensions are installed
- Check log files for detailed error information
- Verify API credentials

## License
Proprietary - © Bullmade

## Support
Contact support@bullmade.com for assistance