<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM Widget Creation Interface (WCI) Version 1.0                |
 +--------------------------------------------------------------------+
 | Copyright Zyxware Technologies (c) 2014                            |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM WCI.                                |
 |                                                                    |
 | CiviCRM WCI is free software; you can copy, modify, and distribute |
 | it under the terms of the GNU Affero General Public License        |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM WCI is distributed in the hope that it will be useful,     |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of     |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact Zyxware           |
 | Technologies at info[AT]zyxware[DOT]com.                           |
 +--------------------------------------------------------------------+
*/
require_once 'CRM/Core/Form.php';
require_once 'wci-helper-functions.php';
require_once 'CRM/Wci/BAO/ProgressBar.php';
require_once 'CRM/Wci/DAO/ProgressBarFormula.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Wci_Form_ProgressBar extends CRM_Core_Form {
  private $_id;
  private $_PBSource_block;
  public $_PBblockId;
  public $_rem_ids;

  function preProcess() {
    $this->_PBSource_block= CRM_Utils_Request::retrieve('PBSource_block',
      'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->_rem_ids = CRM_Utils_Request::retrieve('rem_ids',
      'String', $this, FALSE, NULL, 'REQUEST');
    $this->_PBblockId = CRM_Utils_Request::retrieve('PBblockId', 'Positive',
      $this, FALSE, NULL, 'REQUEST');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->assign('PBblockId', $this->_PBblockId);
    $this->assign('PBSource_block', $this->_PBSource_block);
    CRM_Core_Resources::singleton()->addScriptFile('com.zyxware.civiwci', 'js/addmore.js');

    parent::preProcess();
  }

  function fillData() {
    $count = 1;
    if (isset($this->_id)) {
      /** Updating existing progress bar*/
      $query = "SELECT * FROM civicrm_wci_progress_bar where id=%1";
      $params = array(1 => array($this->_id, 'Integer'));

      $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Wci_DAO_ProgressBar');

      if ($dao->fetch()) {
        $this->setDefaults(array(
          'progressbar_name' => $dao->name));
        $this->setDefaults(array(
          'starting_amount' => $dao->starting_amount));
        $this->setDefaults(array(
          'goal_amount' => $dao->goal_amount));
      }

      $query = "SELECT * FROM civicrm_wci_progress_bar_formula WHERE progress_bar_id =%1";
      $params = array(1 => array($this->_id, 'Integer'));
      $pbSources = array();
      $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Wci_DAO_ProgressBarFormula');
      $remIds = array();

      if(isset($this->_rem_ids)){
        $remIds =  explode(',', $this->_rem_ids);
      }

      while ($dao->fetch()) {
        if(in_array($dao->id, $remIds)) {
          $count++;
          continue;
        }

        $pbSources[] = $count;

        /*Create PB source block*/
        CRM_Wci_Form_PBSource::buildQuickForm($this, $count);


        //save formula id
        $this->addElement('hidden', 'contrib_elem_'.$count , $dao->id);

        $this->setDefaults(array(
              'contribution_page_'.$count => $dao->contribution_page_id));
        $this->setDefaults(array(
              'financial_type_'.$count => $dao->financial_type_id));
        $this->setDefaults(array(
              'percentage_'.$count => $dao->percentage));
        $this->setDefaults(array(
              'contribution_start_date_'.$count => $dao->start_date));
        $this->setDefaults(array(
              'contribution_end_date_'.$count => $dao->end_date));
        //set default for start date and end date.
        $count++;

      }
      CRM_Utils_System::setTitle(ts('Edit Progress Bar'));
      $count--; // because last iteration increments it to the next
      $this->assign('pbSources', $pbSources);
    }
    else {
      /*Create PB source block*/
      CRM_Wci_Form_PBSource::buildQuickForm($this, $count);
      CRM_Utils_System::setTitle(ts('Create Progress Bar'));
    }

    $this->addElement('hidden', 'contrib_count', $count);
   //removed elem id
   $this->addElement('hidden', 'rem_ids', '');
  }

  function buildQuickForm() {
    if(isset($this->_PBSource_block)){
      CRM_Wci_Form_PBSource::buildQuickForm($this, $this->_PBblockId);
      $this->assign('elementNames', $this->getRenderableElementNames());
      return;
    } else {
      $this->add(
        'text', // field type
        'progressbar_name', // field name
        'Name', // field label
        true // is required
      )->setSize(35);
      $this->add(
        'text', // field type
        'starting_amount', // field name
        'Starting amount', // field label
        true // is required
      )->setSize(35);
      $this->add(
        'text', // field type
        'goal_amount', // field name
        'Goal amount', // field label
        true // is required
      )->setSize(35);

      $this->fillData();

      $this->addElement('link', 'addmore_link',' ', 'addmore', 'Add another contribution page or financial type.');

      $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
      ));

      // export form elements
      $this->assign('elementNames', $this->getRenderableElementNames());

      parent::buildQuickForm();
    }
  }

  function postProcess() {

    $errorScope = CRM_Core_TemporaryErrorScope::useException();
    if (isset($this->_id)) {
      try {
        $transaction = new CRM_Core_Transaction();

        $sql = "UPDATE civicrm_wci_progress_bar SET name = %1,
          starting_amount = %2, goal_amount = %3 where id = %4";

        CRM_Core_DAO::executeQuery($sql,
              array(1=>array($_REQUEST['progressbar_name'], 'String'),
              2=>array($_REQUEST['starting_amount'], 'Float'),
              3=>array($_REQUEST['goal_amount'], 'Float'),
              4=>array($this->_id, 'Integer'),
        ));
        /** Delete existiing formula fields and add fields fresh*/
        CRM_Core_DAO::executeQuery('DELETE FROM civicrm_wci_progress_bar_formula
            WHERE progress_bar_id=%1', array(1 => array($this->_id, 'Integer')));
        $elem_added = 0;
        for($i = 1; $elem_added < (int)$_REQUEST['contrib_count']; $i++) {
          if(!isset($_REQUEST['contribution_page_' . (string)$i])) {
            continue;
          }
          $page = 'contribution_page_' . (string)$i;
          $type = 'financial_type_' . (string)$i;
          $perc = 'percentage_' . (string)$i;
          $sdate = 'contribution_start_date_' . (string)$i;
          $edate = 'contribution_end_date_' . (string)$i;

          $sql = "INSERT INTO civicrm_wci_progress_bar_formula
            (contribution_page_id, financial_type_id, progress_bar_id, start_date, end_date, percentage)
            VALUES (%1, %2, %3, %4, %5, %6)";
          $start = NULL;
          $end = NULL;
          if (!empty($_REQUEST[$sdate])) {
            $start = CRM_Utils_Date::processDate($_REQUEST[$sdate], NULL, FALSE, "Ymd");
          }
          if (!empty($_REQUEST[$edate])) {
            $end = CRM_Utils_Date::processDate($_REQUEST[$edate], NULL, FALSE, "Ymd");
          }
          CRM_Core_DAO::executeQuery($sql,
            array(
              1 => array($_REQUEST[$page], 'Integer'),
              2 => array($_REQUEST[$type], 'Integer'),
              3 => array($this->_id, 'Integer'),
              4 => array($start, 'Date'),
              5 => array($end, 'Date'),
              6 => array($_REQUEST[$perc], 'Float')
            ));
            $elem_added++;
        }
        $transaction->commit();
        CRM_Wci_BAO_WidgetCache::deleteWidgetCacheByProgressbar($this->_id);
        CRM_Core_Session::setStatus(ts('Progress bar created successfully'), '', 'success');
        CRM_Utils_System::redirect('progress-bar?reset=1');
      }
      catch (Exception $e) {
        CRM_Core_Session::setStatus(ts('Failed to create progress bar'), '', 'error');
        $transaction->rollback();
      }
    }
    else {
      $sql = "INSERT INTO civicrm_wci_progress_bar
              (name, starting_amount, goal_amount) VALUES (%1, %2, %3)";
      try {
        $transaction = new CRM_Core_Transaction();
        CRM_Core_DAO::executeQuery($sql,
          array(1=>array($_REQUEST['progressbar_name'], 'String'),
          2=>array($_REQUEST['starting_amount'], 'Float'),
          3=>array($_REQUEST['goal_amount'], 'Float'),
        ));
        $progressbar_id = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
        for($i = 1; $i <= (int)$_REQUEST['contrib_count']; $i++) {
          $page = 'contribution_page_' . (string)$i;
          $type = 'financial_type_' . (string)$i;
          $perc = 'percentage_' . (string)$i;
          $sdate = 'contribution_start_date_' . (string)$i;
          $edate = 'contribution_end_date_' . (string)$i;

          $sql = "INSERT INTO civicrm_wci_progress_bar_formula
            (contribution_page_id, financial_type_id, progress_bar_id, start_date, end_date, percentage)
            VALUES (%1, %2, %3, %4, %5, %6)";
          $start = NULL;
          $end = NULL;
          if (!empty($_REQUEST[$sdate])) {
            $start = CRM_Utils_Date::processDate($_REQUEST[$sdate], NULL, FALSE, "Ymd");
          }
          if (!empty($_REQUEST[$edate])) {
            $end = CRM_Utils_Date::processDate($_REQUEST[$edate], NULL, FALSE, "Ymd");
          }
          CRM_Core_DAO::executeQuery($sql,
            array(
              1 => array($_REQUEST[$page], 'Integer'),
              2 => array($_REQUEST[$type], 'Integer'),
              3 => array($progressbar_id, 'Integer'),
              4 => array($start, 'Date'),
              5 => array($end, 'Date'),
              6 => array($_REQUEST[$perc], 'Float')
            ));
        }
        $transaction->commit();
        CRM_Utils_System::redirect('civicrm/wci/progress-bar?reset=1');
      }
      catch (Exception $e) {
        CRM_Core_Session::setStatus(ts('Failed to create Progress bar. ') .
        $e->getMessage(), '', 'error');
        $transaction->rollback();
      }
      $elem = $this->getElement('contrib_count');
      $elem->setValue('1');
    }
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
