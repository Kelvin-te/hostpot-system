/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `billings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_price` int unsigned NOT NULL,
  `package_start` date NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billings_user_id_foreign` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `comment` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `ticket_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_user_id_foreign` (`user_id`),
  KEY `comments_ticket_id_foreign` (`ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `pin` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `router_name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_price` int unsigned NOT NULL,
  `package_start` date NOT NULL,
  `due` int unsigned NOT NULL,
  `status` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `details_user_id_foreign` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotspot_authorizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotspot_authorizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `authorization_key` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `package_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `voucher_id` bigint unsigned DEFAULT NULL,
  `payment_transaction_id` bigint unsigned DEFAULT NULL,
  `client_identifier` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotspot_username` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotspot_password_encrypted` text COLLATE utf8mb4_unicode_ci,
  `client_mac` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','authorized','active','expired','revoked','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `authorized_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `session_timeout` int DEFAULT NULL,
  `idle_timeout` int DEFAULT NULL,
  `rate_limit` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `simultaneous_sessions` int NOT NULL DEFAULT '1',
  `authorization_attributes` json DEFAULT NULL,
  `revoke_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hotspot_authorizations_authorization_key_unique` (`authorization_key`),
  UNIQUE KEY `hotspot_authorizations_payment_package_unique` (`payment_transaction_id`,`package_id`),
  KEY `hotspot_authorizations_router_id_foreign` (`router_id`),
  KEY `hotspot_authorizations_package_id_foreign` (`package_id`),
  KEY `hotspot_authorizations_user_id_foreign` (`user_id`),
  KEY `hotspot_authorizations_voucher_id_foreign` (`voucher_id`),
  KEY `hotspot_authorizations_authorization_key_status_index` (`authorization_key`,`status`),
  KEY `hotspot_authorizations_client_identifier_status_index` (`client_identifier`,`status`),
  KEY `hotspot_authorizations_expires_at_status_index` (`expires_at`,`status`),
  KEY `hotspot_authorizations_hotspot_username_index` (`hotspot_username`),
  KEY `hotspot_authorizations_client_mac_index` (`client_mac`),
  KEY `hotspot_authorizations_status_index` (`status`),
  KEY `hotspot_authorizations_expires_at_index` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotspot_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotspot_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mac_address` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `device_fingerprint` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_id` bigint unsigned NOT NULL,
  `authorization_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `username` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mikrotik_username` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mikrotik_password` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mikrotik_profile` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `started_at` timestamp NOT NULL,
  `expires_at` timestamp NOT NULL,
  `bytes_uploaded` bigint NOT NULL DEFAULT '0',
  `bytes_downloaded` bigint NOT NULL DEFAULT '0',
  `bytes_total` bigint NOT NULL DEFAULT '0',
  `status` enum('active','disconnected','expired','blocked','paused','provisioning_failed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `mikrotik_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hotspot_sessions_session_id_unique` (`session_id`),
  KEY `hotspot_sessions_package_id_foreign` (`package_id`),
  KEY `hotspot_sessions_authorization_id_foreign` (`authorization_id`),
  KEY `hotspot_sessions_user_id_foreign` (`user_id`),
  KEY `hotspot_sessions_mac_address_status_index` (`mac_address`,`status`),
  KEY `hotspot_sessions_expires_at_status_index` (`expires_at`,`status`),
  KEY `hotspot_sessions_session_id_status_index` (`session_id`,`status`),
  KEY `hotspot_sessions_mac_address_index` (`mac_address`),
  KEY `hotspot_sessions_device_fingerprint_index` (`device_fingerprint`),
  KEY `hotspot_sessions_expires_at_index` (`expires_at`),
  KEY `hotspot_sessions_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int unsigned NOT NULL,
  `router_id` bigint unsigned NOT NULL,
  `bandwidth_upload` decimal(8,2) DEFAULT NULL COMMENT 'Upload bandwidth in Mbps',
  `bandwidth_download` decimal(8,2) DEFAULT NULL COMMENT 'Download bandwidth in Mbps',
  `session_timeout` int DEFAULT NULL COMMENT 'Session timeout in hours',
  `idle_timeout` int DEFAULT NULL COMMENT 'Idle timeout in minutes',
  `shared_users` int DEFAULT '1' COMMENT 'Number of shared users allowed',
  `rate_limit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Custom rate limit string',
  `validity_minutes` int DEFAULT NULL COMMENT 'Package validity in minutes',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `packages_name_router_id_unique` (`name`,`router_id`),
  KEY `packages_router_id_foreign` (`router_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `checkout_request_id` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_request_id` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `account_reference` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_desc` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','failed','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `gateway` enum('mpesa','paystack','manual','wallet') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mpesa',
  `type` enum('subscription','one_time','voucher','topup') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time',
  `mpesa_receipt_number` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT NULL,
  `response_code` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_description` text COLLATE utf8mb4_unicode_ci,
  `customer_message` text COLLATE utf8mb4_unicode_ci,
  `result_code` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_description` text COLLATE utf8mb4_unicode_ci,
  `callback_data` json DEFAULT NULL,
  `package_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `session_id` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_transactions_checkout_request_id_unique` (`checkout_request_id`),
  KEY `payment_transactions_package_id_foreign` (`package_id`),
  KEY `payment_transactions_user_id_foreign` (`user_id`),
  KEY `payment_transactions_status_created_at_index` (`status`,`created_at`),
  KEY `payment_transactions_phone_number_index` (`phone_number`),
  KEY `payment_transactions_mpesa_receipt_number_index` (`mpesa_receipt_number`),
  KEY `payment_transactions_session_id_index` (`session_id`),
  KEY `payment_transactions_gateway_status_created_at_index` (`gateway`,`status`,`created_at`),
  KEY `payment_transactions_router_id_created_at_index` (`router_id`,`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_price` int unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `billing_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_user_id_foreign` (`user_id`),
  KEY `payments_billing_id_foreign` (`billing_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `routers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `routers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_port` int unsigned NOT NULL DEFAULT '8728',
  `hotspot_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `hotspot_interface` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotspot_server_ip` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `packages_sync_count` int NOT NULL DEFAULT '0',
  `packages_unsync_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `routers_name_unique` (`name`),
  UNIQUE KEY `routers_identifier_unique` (`identifier`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mail_server` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_username` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_password` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_port` int DEFAULT NULL,
  `mail_from_address` int DEFAULT NULL,
  `mail_from_name` int DEFAULT NULL,
  `app_name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_username` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_password` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bill_at` int unsigned DEFAULT '0',
  `disconnect_at` int unsigned DEFAULT '0',
  `walled_garden_domains` json DEFAULT NULL,
  `walled_garden_ips` json DEFAULT NULL,
  `walled_garden_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `company_name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_address` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_phone` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_email` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_api_key` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_sender_id` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_alerts_enabled` tinyint(1) DEFAULT '1',
  `expiry_warnings_enabled` tinyint(1) DEFAULT '1',
  `mpesa_api_key` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_shortcode` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mpesa_passkey` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_payment_gateway` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_payment_api_key` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_timeout_minutes` int DEFAULT '15',
  `smtp_host` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_port` int DEFAULT NULL,
  `smtp_username` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_password` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_encryption` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_from_address` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_from_name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_min_length` int DEFAULT '8',
  `password_require_uppercase` tinyint(1) DEFAULT '1',
  `password_require_number` tinyint(1) DEFAULT '1',
  `password_require_special` tinyint(1) DEFAULT '1',
  `session_timeout_minutes` int DEFAULT '60',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `support_phone` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_email` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_hours` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `free_package_data_limit_mb` int DEFAULT '500',
  `free_package_validity_hours` int DEFAULT '24',
  `anti_abuse_enabled` tinyint(1) DEFAULT '1',
  `invoice_prefix` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT 'INV-',
  `payment_terms` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT 'Net 30',
  `invoice_footer` text COLLATE utf8mb4_unicode_ci,
  `company_logo` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_css` text COLLATE utf8mb4_unicode_ci,
  `backup_schedule` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT 'daily',
  `backup_retention_days` int DEFAULT '30',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_email_unique` (`email`),
  UNIQUE KEY `staff_phone_unique` (`phone`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_password_reset_tokens` (
  `email` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_number_unique` (`number`),
  KEY `tickets_user_id_foreign` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `time_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timezone` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_spent` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sessions` int NOT NULL DEFAULT '0',
  `total_data_used` bigint NOT NULL DEFAULT '0',
  `last_session_at` timestamp NULL DEFAULT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','suspended','banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  KEY `users_router_id_foreign` (`router_id`),
  KEY `users_mac_address_index` (`mac_address`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_id` bigint unsigned NOT NULL,
  `status` enum('active','used','expired','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `used_at` timestamp NULL DEFAULT NULL,
  `used_by_mac` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `used_by_ip` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vouchers_code_unique` (`code`),
  KEY `vouchers_package_id_foreign` (`package_id`),
  KEY `vouchers_session_id_foreign` (`session_id`),
  KEY `vouchers_code_status_index` (`code`,`status`),
  KEY `vouchers_status_expires_at_index` (`status`,`expires_at`),
  KEY `vouchers_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallet_transactions_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `wallet_transactions_reference_index` (`reference`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_100000_create_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2022_01_01_000000_disable_foreign_key_checks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2023_04_25_102126_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2023_05_01_165607_create_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2023_05_02_170904_create_billings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2023_05_13_132924_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2023_05_15_102727_create_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2023_05_15_102735_create_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2023_05_24_073021_create_time_zones_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_01_01_000000_create_payment_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2024_01_03_000000_create_vouchers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2024_12_28_120000_create_sms_verifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_08_17_000000_create_hotspot_and_staff_schema',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_08_17_000001_create_captive_portal_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_08_17_000002_create_hotspot_authorizations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_08_17_000004_create_radius_accounting_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_08_17_000005_consolidate_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_08_17_000006_consolidate_packages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_08_17_000007_consolidate_hotspot_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_08_17_000008_consolidate_routers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_08_19_100000_create_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_08_21_000000_create_staff_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_08_24_000000_drop_radius_accounting_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_08_28_200000_add_voucher_id_to_payment_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_09_02_000000_enhance_payment_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_09_02_000001_add_provisioning_failed_status_to_hotspot_sessions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_09_02_000001_enhance_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_09_02_000002_create_wallet_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_09_03_000001_rename_radius_columns_in_hotspot_authorizations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2029_12_31_235959_enable_foreign_key_checks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_09_05_000000_drop_unused_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_09_05_000001_drop_wingufi_core_authorization_id',3);
