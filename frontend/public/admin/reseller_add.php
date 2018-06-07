<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace iMSCP;

use iMSCP\Authentication\AuthenticationService;
use iMSCP\Functions\Mail;
use iMSCP\Functions\View;
use Zend\EventManager\Event;
use Zend\Form\Form;

/**
 * Retrieve form data
 *
 * @return array Reference to array of data
 */
function getFormData()
{
    static $data = NULL;

    if (NULL !== $data) {
        return $data;
    }

    $stmt = execQuery('SELECT ip_id, ip_number FROM server_ips ORDER BY ip_number');
    if ($stmt->rowCount()) {
        $data['server_ips'] = $stmt->fetchAll();
    } else {
        View::setPageMessage(tr('Unable to get the IP address list. Please fix this problem.'), 'error');
        redirectTo('users.php');
    }

    $phpini = PhpIni::getInstance();

    foreach (
        [
            'max_dmn_cnt'                  => '0',
            'max_sub_cnt'                  => '0',
            'max_als_cnt'                  => '0',
            'max_mail_cnt'                 => '0',
            'max_ftp_cnt'                  => '0',
            'max_sql_db_cnt'               => '0',
            'max_sql_user_cnt'             => '0',
            'max_traff_amnt'               => '0',
            'max_disk_amnt'                => '0',
            'support_system'               => 'yes',
            'php_ini_system'               => $phpini->getResellerPermission('phpiniSystem'),
            'php_ini_al_config_level'      => $phpini->getResellerPermission('phpiniConfigLevel'),
            'php_ini_al_allow_url_fopen'   => $phpini->getResellerPermission('phpiniAllowUrlFopen'),
            'php_ini_al_display_errors'    => $phpini->getResellerPermission('phpiniDisplayErrors'),
            'php_ini_al_disable_functions' => $phpini->getResellerPermission('phpiniDisableFunctions'),
            'php_ini_al_mail_function'     => $phpini->getResellerPermission('phpiniMailFunction'),
            'post_max_size'                => $phpini->getResellerPermission('phpiniPostMaxSize'),
            'upload_max_filesize'          => $phpini->getResellerPermission('phpiniUploadMaxFileSize'),
            'max_execution_time'           => $phpini->getResellerPermission('phpiniMaxExecutionTime'),
            'max_input_time'               => $phpini->getResellerPermission('phpiniMaxInputTime'),
            'memory_limit'                 => $phpini->getResellerPermission('phpiniMemoryLimit')
        ] as $key => $value
    ) {
        if (isset($_POST[$key])) {
            $data[$key] = cleanInput($_POST[$key]);
            continue;
        }

        $data[$key] = $value;
    }

    $data['reseller_ips'] = isset($_POST['reseller_ips']) && is_array($_POST['reseller_ips']) ? $_POST['reseller_ips'] : [];
    return $data;
}

/**
 * Generates IP list form
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generateIpListForm(TemplateEngine $tpl)
{
    $data = getFormData();
    $tpl->assign('TR_IPS', toHtml(tr('IP addresses')));

    Application::getInstance()->getEventManager()->attach(Events::onGetJsTranslations, function (Event $e) {
        $e->getParam('translations')->core['dataTable'] = View::getDataTablesPluginTranslations(false);
        $e->getParam('translations')->core['available'] = tr('Available');
        $e->getParam('translations')->core['assigned'] = tr('Assigned');
    });

    foreach ($data['server_ips'] as $ipData) {
        $tpl->assign([
            'IP_VALUE'    => toHtml($ipData['ip_id']),
            'IP_NUM'      => toHtml($ipData['ip_number'] == '0.0.0.0' ? tr('Any') : $ipData['ip_number']),
            'IP_SELECTED' => in_array($ipData['ip_id'], $data['reseller_ips']) ? ' selected' : ''
        ]);
        $tpl->parse('IP_ENTRY', '.ip_entry');
    }
}

/**
 * Generates features form
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generateLimitsForm(TemplateEngine $tpl)
{
    $data = getFormData();
    $tpl->assign([
        'TR_ACCOUNT_LIMITS'   => toHtml(tr('Account limits')),
        'TR_MAX_DMN_CNT'      => toHtml(tr('Domains limit')) . '<br><i>(0 ∞)</i>',
        'MAX_DMN_CNT'         => toHtml($data['max_dmn_cnt']),
        'TR_MAX_SUB_CNT'      => toHtml(tr('Subdomains limit')) . '<br><i>(-1 ' . toHtml(tr('disabled')) . ', 0 ∞)</i>',
        'MAX_SUB_CNT'         => toHtml($data['max_sub_cnt']),
        'TR_MAX_ALS_CNT'      => toHtml(tr('Domain aliases limit')) . '<br><i>(-1 ' . toHtml(tr('disabled')) . ', 0 ∞)</i>',
        'MAX_ALS_CNT'         => toHtml($data['max_als_cnt']),
        'TR_MAX_MAIL_CNT'     => toHtml(tr('Mail accounts limit')) . '<br><i>(-1 ' . toHtml(tr('disabled')) . ', 0 ∞)</i>',
        'MAX_MAIL_CNT'        => toHtml($data['max_mail_cnt']),
        'TR_MAX_FTP_CNT'      => toHtml(tr('FTP accounts limit')) . '<br><i>(-1 ' . toHtml(tr('disabled')) . ', 0 ∞)</i>',
        'MAX_FTP_CNT'         => toHtml($data['max_ftp_cnt']),
        'TR_MAX_SQL_DB_CNT'   => toHtml(tr('SQL databases limit')) . '<br><i>(-1 ' . toHtml(tr('disabled')) . ', 0 ∞)</i>',
        'MAX_SQL_DB_CNT'      => toHtml($data['max_sql_db_cnt']),
        'TR_MAX_SQL_USER_CNT' => toHtml(tr('SQL users limit')) . '<br><i>(-1 ' . toHtml(tr('disabled')) . ', 0 ∞)</i>',
        'MAX_SQL_USER_CNT'    => toHtml($data['max_sql_user_cnt']),
        'TR_MAX_TRAFF_AMNT'   => toHtml(tr('Monthly traffic limit [MiB]')) . '<br><i>(0 ∞)</i>',
        'MAX_TRAFF_AMNT'      => toHtml($data['max_traff_amnt']),
        'TR_MAX_DISK_AMNT'    => toHtml(tr('Disk space limit [MiB]')) . '<br><i>(0 ∞)</i>',
        'MAX_DISK_AMNT'       => toHtml($data['max_disk_amnt'])
    ]);
}

/**
 * Generates features form
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generateFeaturesForm(TemplateEngine $tpl)
{
    $data = getFormData();
    $tpl->assign([
        'TR_FEATURES'                        => toHtml(tr('Features')),
        'TR_SETTINGS'                        => toHtml(tr('PHP Settings')),
        'TR_PHP_EDITOR'                      => toHtml(tr('PHP Editor')),
        'TR_PHP_EDITOR_SETTINGS'             => toHtml(tr('PHP Settings')),
        'TR_PERMISSIONS'                     => toHtml(tr('PHP Permissions')),
        'TR_DIRECTIVES_VALUES'               => toHtml(tr('PHP Configuration options')),
        'TR_FIELDS_OK'                       => toHtml(tr('All fields are valid.')),
        'PHP_INI_SYSTEM_YES'                 => $data['php_ini_system'] == 'yes' ? ' checked' : '',
        'PHP_INI_SYSTEM_NO'                  => $data['php_ini_system'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_AL_CONFIG_LEVEL'         => toHtml(tr('PHP configuration level')),
        'TR_PHP_INI_AL_CONFIG_LEVEL_HELP'    => toHtml(tr('Per site: Different PHP configuration for each customer domain, including subdomains<b>Per domain: Identical PHP configuration for each customer domain, including subdomains<br>Per user: Identical PHP configuration for all customer domains, including subdomains'), 'htmlAttr'),
        'TR_PER_DOMAIN'                      => toHtml(tr('Per domain')),
        'TR_PER_SITE'                        => toHtml(tr('Per site')),
        'TR_PER_USER'                        => toHtml(tr('Per user')),
        'PHP_INI_AL_CONFIG_LEVEL_PER_DOMAIN' => $data['php_ini_al_config_level'] == 'per_domain' ? ' checked' : '',
        'PHP_INI_AL_CONFIG_LEVEL_PER_SITE'   => $data['php_ini_al_config_level'] == 'per_site' ? ' checked' : '',
        'PHP_INI_AL_CONFIG_LEVEL_PER_USER'   => $data['php_ini_al_config_level'] == 'per_user' ? ' checked' : '',
        'TR_PHP_INI_AL_ALLOW_URL_FOPEN'      => tr('Can edit the PHP %s configuration option', '<strong>allow_url_fopen</strong>'),
        'PHP_INI_AL_ALLOW_URL_FOPEN_YES'     => $data['php_ini_al_allow_url_fopen'] == 'yes' ? ' checked' : '',
        'PHP_INI_AL_ALLOW_URL_FOPEN_NO'      => $data['php_ini_al_allow_url_fopen'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_AL_DISPLAY_ERRORS'       => tr('Can edit the PHP %s configuration option', '<strong>display_errors</strong>'),
        'PHP_INI_AL_DISPLAY_ERRORS_YES'      => $data['php_ini_al_display_errors'] == 'yes' ? ' checked' : '',
        'PHP_INI_AL_DISPLAY_ERRORS_NO'       => $data['php_ini_al_display_errors'] != 'yes' ? ' checked' : '',
        'TR_MEMORY_LIMIT'                    => tr('PHP %s configuration option', '<strong>memory_limit</strong>'),
        'MEMORY_LIMIT'                       => toHtml($data['memory_limit']),
        'TR_UPLOAD_MAX_FILESIZE'             => tr('PHP %s configuration option', '<strong>upload_max_filesize</strong>'),
        'UPLOAD_MAX_FILESIZE'                => toHtml($data['upload_max_filesize']),
        'TR_POST_MAX_SIZE'                   => tr('PHP %s configuration option', '<strong>post_max_size</strong>'),
        'POST_MAX_SIZE'                      => toHtml($data['post_max_size']),
        'TR_MAX_EXECUTION_TIME'              => tr('PHP %s configuration option', '<strong>max_execution_time</strong>'),
        'MAX_EXECUTION_TIME'                 => toHtml($data['max_execution_time']),
        'TR_MAX_INPUT_TIME'                  => tr('PHP %s configuration option', '<strong>max_input_time</strong>'),
        'MAX_INPUT_TIME'                     => toHtml($data['max_input_time']),
        'TR_SUPPORT_SYSTEM'                  => toHtml(tr('Support system')),
        'SUPPORT_SYSTEM_YES'                 => $data['support_system'] == 'yes' ? ' checked' : '',
        'SUPPORT_SYSTEM_NO'                  => $data['support_system'] != 'yes' ? ' checked' : '',
        'TR_PHP_INI_PERMISSION_HELP'         => toHtml(tr('If set to `yes`, the reseller can allows his customers to edit this PHP configuration option.'), 'htmlAttr'),
        'TR_PHP_INI_AL_MAIL_FUNCTION_HELP'   => toHtml(tr('If set to `yes`, the reseller can enable/disable the PHP mail function for his customers, else, the PHP mail function is disabled.'), 'htmlAttr'),
        'TR_YES'                             => toHtml(tr('Yes')),
        'TR_NO'                              => toHtml(tr('No')),
        'TR_MIB'                             => toHtml(tr('MiB')),
        'TR_SEC'                             => toHtml(tr('Sec.'))
    ]);

    Application::getInstance()->getEventManager()->attach(Events::onGetJsTranslations, function (Event $e) {
        $translations = $e->getParam('translations');
        $translations['core']['close'] = tr('Close');
        $translations['core']['fields_ok'] = tr('All fields are valid.');
        $translations['core']['out_of_range_value_error'] = tr('Value for the PHP %%s directive must be in range %%d to %%d.');
        $translations['core']['lower_value_expected_error'] = tr('%%s cannot be greater than %%s.');
        $translations['core']['error_field_stack'] = Application::getInstance()->getRegistry()->has('errFieldsStack')
            ? Application::getInstance()->getRegistry()->get('errFieldsStack') : [];
    });

    if (strpos(Application::getInstance()->getConfig()['iMSCP::Servers::Httpd'], '::Apache2::') !== false) {
        $apacheConfig = loadServiceConfigFile(Application::getInstance()->getConfig()['CONF_DIR'] . '/apache/apache.data');
        $isApacheItk = $apacheConfig['HTTPD_MPM'] == 'itk';
    } else {
        $isApacheItk = false;
    }

    if (!$isApacheItk) {
        $tpl->assign([
            'TR_PHP_INI_AL_DISABLE_FUNCTIONS'  => tr('Can edit the PHP %s configuration option', '<strong>disable_functions</strong>'),
            'PHP_INI_AL_DISABLE_FUNCTIONS_YES' => $data['php_ini_al_disable_functions'] == 'yes' ? ' checked' : '',
            'PHP_INI_AL_DISABLE_FUNCTIONS_NO'  => $data['php_ini_al_disable_functions'] != 'yes' ? ' checked' : '',
            'TR_PHP_INI_AL_MAIL_FUNCTION'      => tr('Can use the PHP %s function', '<strong>mail</strong>'),
            'PHP_INI_AL_MAIL_FUNCTION_YES'     => $data['php_ini_al_mail_function'] == 'yes' ? ' checked' : '',
            'PHP_INI_AL_MAIL_FUNCTION_NO'      => $data['php_ini_al_mail_function'] != 'yes' ? ' checked' : '',
        ]);
        return;
    }

    $tpl->assign('PHP_EDITOR_DISABLE_FUNCTIONS_BLOCK', '');
    $tpl->assign('PHP_EDITOR_MAIL_FUNCTION_BLOCK', '');
}

/**
 * Add reseller user
 *
 * @param Form $form
 * @return void
 */
function addResellerUser(Form $form)
{
    $error = false;
    $errFieldsStack = [];

    $db = Application::getInstance()->getDb();

    try {
        // Check for login and personal data
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $fieldname => $msgsStack) {
                $errFieldsStack[] = $fieldname;
                foreach ($msgsStack as $msg) {
                    View::setPageMessage(toHtml($msg), 'error');
                }
            }
        }

        $data = getFormData();

        // Check for ip addresses - We are safe here

        $resellerIps = array_intersect($data['reseller_ips'], array_column($data['server_ips'], 'ip_id'));
        if (empty($resellerIps)) {
            View::setPageMessage(tr('You must assign at least one IP to this reseller.'), 'error');
            $error = true;
        } else {
            sort($resellerIps, SORT_NUMERIC);
        }

        // Check for max domains limit
        if (!validateLimit($data['max_dmn_cnt'], NULL)) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('domain')), 'error');
            $errFieldsStack[] = 'max_dmn_cnt';
        }

        // Check for max subdomains limit
        if (!validateLimit($data['max_sub_cnt'])) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('subdomains')), 'error');
            $errFieldsStack[] = 'max_sub_cnt';
        }

        // check for max domain aliases limit
        if (!validateLimit($data['max_als_cnt'])) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('domain aliases')), 'error');
            $errFieldsStack[] = 'max_als_cnt';
        }

        // Check for max mail accounts limit
        if (!validateLimit($data['max_mail_cnt'])) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('mail accounts')), 'error');
            $errFieldsStack[] = 'max_mail_cnt';
        }

        // Check for max ftp accounts limit
        if (!validateLimit($data['max_ftp_cnt'])) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('Ftp accounts')), 'error');
            $errFieldsStack[] = 'max_ftp_cnt';
        }

        // Check for max Sql databases limit
        if (!validateLimit($data['max_sql_db_cnt'])) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('SQL databases')), 'error');
            $errFieldsStack[] = 'max_sql_db_cnt';
        } elseif ($_POST['max_sql_db_cnt'] == -1 && $_POST['max_sql_user_cnt'] != -1) {
            View::setPageMessage(tr('SQL database limit is disabled but SQL user limit is not.'), 'error');
            $errFieldsStack[] = 'max_sql_db_cnt';
        }

        // Check for max Sql users limit
        if (!validateLimit($data['max_sql_user_cnt'])) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('SQL users')), 'error');
            $errFieldsStack[] = 'max_sql_user_cnt';
        } elseif ($_POST['max_sql_user_cnt'] == -1 && $_POST['max_sql_db_cnt'] != -1) {
            View::setPageMessage(tr('SQL user limit is disabled but SQL database limit is not.'), 'error');
            $errFieldsStack[] = 'max_sql_user_cnt';
        }

        // Check for max monthly traffic limit
        if (!validateLimit($data['max_traff_amnt'], NULL)) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('traffic')), 'error');
            $errFieldsStack[] = 'max_traff_amnt';
        }

        // Check for max disk space limit
        if (!validateLimit($data['max_disk_amnt'], NULL)) {
            View::setPageMessage(tr('Incorrect limit for %s.', tr('Disk space')), 'error');
            $errFieldsStack[] = 'max_disk_amnt';
        }

        $db->getDriver()->getConnection()->beginTransaction();

        // Check for PHP settings
        $phpini = PhpIni::getInstance();
        $phpini->setResellerPermission('phpiniSystem', $data['php_ini_system']);

        if ($phpini->resellerHasPermission('phpiniSystem')) {
            $phpini->setResellerPermission('phpiniConfigLevel', $data['php_ini_al_config_level']);
            $phpini->setResellerPermission('phpiniAllowUrlFopen', $data['php_ini_al_allow_url_fopen']);
            $phpini->setResellerPermission('phpiniDisplayErrors', $data['php_ini_al_display_errors']);
            $phpini->setResellerPermission('phpiniDisableFunctions', $data['php_ini_al_disable_functions']);
            $phpini->setResellerPermission('phpiniMailFunction', $data['php_ini_al_mail_function']);

            $phpini->setResellerPermission('phpiniMemoryLimit', $data['memory_limit']); // Must be set before phpiniPostMaxSize
            $phpini->setResellerPermission('phpiniPostMaxSize', $data['post_max_size']); // Must be set before phpiniUploadMaxFileSize
            $phpini->setResellerPermission('phpiniUploadMaxFileSize', $data['upload_max_filesize']);
            $phpini->setResellerPermission('phpiniMaxExecutionTime', $data['max_execution_time']);
            $phpini->setResellerPermission('phpiniMaxInputTime', $data['max_input_time']);
        }

        if (empty($errFieldsStack) && !$error) {
            $identity = Application::getInstance()->getAuthService()->getIdentity();

            Application::getInstance()->getEventManager()->trigger(Events::onBeforeAddUser, NULL, [
                'userData' => $form->getValues()
            ]);

            execQuery(
                '
                    INSERT INTO admin (
                        admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip, city, state, country, email, phone,
                        fax, street1, street2, gender
                    ) VALUES (
                        ?, ?, ?, unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ',
                [
                    $form->getValue('admin_name'), Crypt::bcrypt($form->getValue('admin_pass')), 'reseller', $identity->getUserId(),
                    $form->getValue('fname'), $form->getValue('lname'), $form->getValue('firm'), $form->getValue('zip'), $form->getValue('city'),
                    $form->getValue('state'), $form->getValue('country'), encodeIdna($form->getValue('email')), $form->getValue('phone'),
                    $form->getValue('fax'), $form->getValue('street1'), $form->getValue('street2'), $form->getValue('gender')
                ]
            );

            $resellerId = $db->getDriver()->getLastGeneratedValue();
            $cfg = Application::getInstance()->getConfig();

            execQuery('INSERT INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)', [
                $resellerId, $cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']
            ]);
            execQuery(
                '
                    INSERT INTO reseller_props (
                        reseller_id, reseller_ips, max_dmn_cnt, current_dmn_cnt, max_sub_cnt, current_sub_cnt, max_als_cnt, current_als_cnt,
                        max_mail_cnt, current_mail_cnt, max_ftp_cnt, current_ftp_cnt, max_sql_db_cnt, current_sql_db_cnt, max_sql_user_cnt,
                        current_sql_user_cnt, max_traff_amnt, current_traff_amnt, max_disk_amnt, current_disk_amnt, support_system, php_ini_system,
                        php_ini_al_config_level, php_ini_al_disable_functions, php_ini_al_mail_function, php_ini_al_allow_url_fopen,
                        php_ini_al_display_errors, php_ini_max_post_max_size, php_ini_max_upload_max_filesize,php_ini_max_max_execution_time,
                        php_ini_max_max_input_time, php_ini_max_memory_limit
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ',
                [
                    $resellerId, implode(',', $resellerIps), $data['max_dmn_cnt'], '0', $data['max_sub_cnt'], '0', $data['max_als_cnt'], '0',
                    $data['max_mail_cnt'], '0', $data['max_ftp_cnt'], '0', $data['max_sql_db_cnt'], '0', $data['max_sql_user_cnt'], '0',
                    $data['max_traff_amnt'], '0', $data['max_disk_amnt'], '0', $data['support_system'],
                    $phpini->getResellerPermission('phpiniSystem'),
                    $phpini->getResellerPermission('phpiniConfigLevel'),
                    $phpini->getResellerPermission('phpiniDisableFunctions'),
                    $phpini->getResellerPermission('phpiniMailFunction'),
                    $phpini->getResellerPermission('phpiniAllowUrlFopen'),
                    $phpini->getResellerPermission('phpiniDisplayErrors'),
                    $phpini->getResellerPermission('phpiniPostMaxSize'),
                    $phpini->getResellerPermission('phpiniUploadMaxFileSize'),
                    $phpini->getResellerPermission('phpiniMaxExecutionTime'),
                    $phpini->getResellerPermission('phpiniMaxInputTime'),
                    $phpini->getResellerPermission('phpiniMemoryLimit')
                ]
            );

            Application::getInstance()->getEventManager()->trigger(Events::onAfterAddUser, NULL, [
                'userId'   => $resellerId,
                'userData' => $form->getValues()
            ]);

            $db->getDriver()->getConnection()->commit();
            Mail::sendWelcomeMail(
                $identity->getUserId(), $form->getValue('admin_name'), $form->getValue('admin_pass'), $form->getValue('email'),
                $form->getValue('fname'), $form->getValue('lname'), tr('Reseller')
            );
            writeLog(sprintf(
                'The %s reseller has been added by %s', $form->getValue('admin_name'),
                Application::getInstance()->getAuthService()->getIdentity()->getUsername()),
                E_USER_NOTICE
            );
            View::setPageMessage('Reseller has been added.', 'success');
            redirectTo('users.php');
        } elseif (!empty($errFieldsStack)) {
            Application::getInstance()->getRegistry()->set('errFieldsStack', $errFieldsStack);
        }
    } catch (\Exception $e) {
        $db->getDriver()->getConnection()->rollBack();
        throw $e;
    }
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine instance
 * @param Form $form
 * @return void
 */
function generatePage(TemplateEngine $tpl, Form $form)
{
    $tpl->form = $form;

    generateIpListForm($tpl);
    generateLimitsForm($tpl);
    generateFeaturesForm($tpl);
}

require_once 'application.php';

Application::getInstance()->getAuthService()->checkIdentity(AuthenticationService::ADMIN_IDENTITY_TYPE);
Application::getInstance()->getEventManager()->trigger(Events::onAdminScriptStart);

$phpini = PhpIni::getInstance();
$phpini->loadResellerPermissions();

$form = getUserLoginDataForm(true, true)->addElements(getUserPersonalDataForm()->getElements());
$form->setDefault('gender', 'U');

if(Application::getInstance()->getRequest()->isPost()) {
 addResellerUser($form);
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                             => 'shared/layouts/ui.tpl',
    'page'                               => 'admin/reseller_add.phtml',
    'page_message'                       => 'layout',
    'ip_entry'                           => 'page',
    'php_editor_disable_functions_block' => 'page',
    'php_editor_mail_function_block'     => 'page'
]);
$tpl->assign('TR_PAGE_TITLE', toHtml(tr('Admin / Users / Add Reseller')));
View::generateNavigation($tpl);
generatePage($tpl, $form);
View::generatePageMessages($tpl);
$tpl->parse('LAYOUT_CONTENT', 'page');
Application::getInstance()->getEventManager()->trigger(Events::onAdminScriptEnd, NULL, ['templateEngine' => $tpl]);
$tpl->prnt();
unsetMessages();