<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die;
}

require_once('Cleantalk.php' );
require_once('CleantalkRequest.php' );
require_once('CleantalkResponse.php' );
require_once('CleantalkHelper.php' );
require_once('CleantalkSFW.php' );

/**
 * cleantalk.org is a cloud antispam service 
 *
 * @see http://stopforumspam.com/faq
 * @author Denis Shagimuratov <shagimuratov@cleantalk.org>
 * @license GPL v2 or higher
 */

/**
 * Access key for cleantalk.org
 * @see https://clenatalk.org/register
 */
$wgCTAccessKey = '';

/**
 * Check registrations, when there is errors with connection to service
 * true - option enabled
 * false - option disabled
 */
$wgCTCheckNoConnect = true;

/**
 * Cloud URL 
 */
$wgCTServerURL = 'http://moderate.cleantalk.org/api2.0';

/**
 * Extension agent name and version 
 */
$wgCTAgent = 'mediawiki-21';

/**
 * Extension name 
 */
$wgCTExtName = 'Antispam by CleanTalk';

/**
 * Admin notificaction account ID 
 */
$wgCTAdminAccountId = 1;

/**
 * Admin notificactions interval in seconds
 */
$wgCTAdminNotificaionInteval = 10800;

/**
 * Show link to CleanTalk
 * Enabling this option places a small link under the comment form that lets others know what antispam tool protects your site.
 */
$wgCTShowLink = true;

/**
 * SpamFireWall
 * Enabling this option makes SpamFileWall feature active.
 */
$wgCTSFW = false;

/**
 * Edit new edits only
 * Check all edits or new edits only.
 */
$wgCTNewEditsOnly = false;

/**
 * Minimal edits count to skip edit checking
 * Checking will skipped for users with getEditCount() more than this value.
 * It doesn'f affect when $wgCTNewEditsOnly == true
 */
$wgCTMinEditCount = 10;

/**
 * Extension settings store file 
 */
$wgCTDataStoreFile = __DIR__ . '/Antispam.store.dat';

$wgExtensionCredits['antispam'][] = array(
	'path' => __FILE__,
	'name' => $wgCTExtName, 
	'author' => 'Denis Shagimuratov',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Antispam',
	'descriptionmsg' => 'cleantalk-desc',
	'version' => '2.1',
);

$wgAutoloadClasses['CTBody'] = __DIR__ . '/Antispam.body.php';
$wgAutoloadClasses['CTHooks'] = __DIR__ . '/Antispam.hooks.php';

$wgHooks['AbortNewAccount'][] = 'CTHooks::onAbortNewAccount';

$wgHooks['EditFilter'][] = 'CTHooks::onEditFilter';

$wgHooks['UploadVerifyFile'][] = 'CTHooks::onUploadFilter';

// Skip edit test for Administrators
$wgGroupPermissions['sysop']['cleantalk-bypass'] = true;

$wgHooks["SkinAfterBottomScripts"][] = "CTHooks::onSkinAfterBottomScripts";
$wgHooks['TitleMove'][] = 'CTHooks::onTitleMove';
