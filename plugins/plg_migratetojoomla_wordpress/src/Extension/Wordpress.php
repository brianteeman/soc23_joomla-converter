<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.contact
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MigrateToJoomla\Wordpress\Extension;

use Hoa\Event\Test\Unit\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\CMS\Form\Form;
use ReflectionClass;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;
use stdClass;
use Joomla\Database\DatabaseDriver;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Wordpress Plugin
 *
 * @since  1.0
 */

final class Wordpress extends CMSPlugin implements SubscriberInterface
{

    /**
     * @var object Database object
     * 
     * @since 1.0
     */
    public $db;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareFormmigrate' => 'onContentPrepareForm',
            'migratetojoomla_user' => 'importUsers'
        ];
    }

    /**
     * The form event.
     *
     * @param   Form      $form  The form
     * @param   stdClass  $data  The data
     *
     * @return   boolean
     *
     * @since   4.0.0
     */
    public function onContentPrepareForm(EventInterface $event)
    {
        $form = $event->getArgument('form');
        $formName = $event->getArgument('formname');

        if ($this->_name !== $event->getArgument('framework')) {
            return true;
        }

        $allowedForms = [
            'com_migratetojoomla.parameter'
        ];

        if (!in_array($formName, $allowedForms, true)) {
            return true;
        }

        Form::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');

        $form->loadFile('wordpress', false);

        $data = Factory::getApplication()->getUserState('com_migratetojoomla.parameter', []);

        if (array_key_exists('frameworkparams', $data)) {

            // form data of plugin form
            $dataextend = $data['frameworkparams'];

            foreach ($dataextend as $field => $value) {
                $form->setValue($field, 'frameworkparams', $value);
            }
        }

        return true;
    }

    /**
     * Method to set database $db if it is not set
     * 
     * @param array form data
     * @return boolean True on success
     * 
     * @since 1.0
     */
    public static function setdatabase($instance, $data = [])
    {
        if (\is_resource($instance->db)) {
            return true;
        }

        $options = [
            'driver'    => $data['dbdriver'],
            'host'      => $data['dbhostname'] . ':' . $data['dbport'],
            'user'      => $data['dbusername'],
            'password'  => $data['dbpassword'],
            'database'  => $data['dbname'],
            'prefix'    => $data['dbtableprefix'],
        ];

        try {
            $db = DatabaseDriver::getInstance($options);
            $db->getVersion();
            $instance->db = $db;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /** 
     * Method to import user table
     * 
     * @since 1.0
     */
    public function importUsers(EventInterface $event)
    {   
        // echo "kaushik is here";
        // die;
        if (!\is_resource($this->db)) {
            self::setdatabase($this, Factory::getApplication()->getUserState('com_migratetojoomla.information', []));
        }
        $data = Factory::getApplication()->getUserState('com_migratetojoomla.information', []);
        $db = $this->db;

        // Specify the table name
        $tableName = rtrim($data['dbtableprefix'], '_') . '_users';
        $config['dbo'] = $db;
        $tablePrefix = Factory::getConfig()->get('dbprefix');

        // load data from framework table
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName($tableName));

        $db->setQuery($query);
        $results = $db->loadAssocList();

        foreach ($results as $row) {

            $user = new stdClass();
            $user->id = $row['ID'];
            $user->name = $row['display_name'];
            $user->username = $row['user_login'];
            $user->email = $row['user_email'];
            $user->registerDate = $row['user_registered'];
            $user->activation = $row['user_activation_key'];
            $user->requireReset = 1;
            $user->params = '{"admin_style":"","admin_language":"","language":"","editor":"","timezone":"","a11y_mono":"0","a11y_contrast":"0","a11y_highlight":"0","a11y_font":"0"}';

            $jdb = Factory::getDbo()->insertObject($tablePrefix . 'users', $user);
        }
    }
}
