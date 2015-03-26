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
require_once 'CRM/Core/Page.php';
require_once 'CRM/Wci/DAO/ProgressBar.php';

class CRM_Wci_Page_ProgressBarList extends CRM_Core_Page {
  private static $_actionLinks;

  function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );
    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    if ($action & CRM_Core_Action::UPDATE) {
      $controller = new CRM_Core_Controller_Simple('CRM_Wci_Form_ProgressBar',
        'Edit Progressbar',
        CRM_Core_Action::UPDATE
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();

    } elseif ($action & CRM_Core_Action::COPY) {

      try {
        $sql = "INSERT INTO civicrm_wci_progress_bar (name, starting_amount, goal_amount)
        SELECT concat(name, '-', (SELECT MAX(id) FROM civicrm_wci_progress_bar)),
        starting_amount, goal_amount FROM civicrm_wci_progress_bar
        WHERE id=%1";

        CRM_Core_DAO::executeQuery($sql,
              array(1=>array($id, 'Integer'),
        ));

        $new_pb_id = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');

        $sql = "INSERT INTO civicrm_wci_progress_bar_formula
            (contribution_page_id, financial_type_id, progress_bar_id, start_date, end_date, percentage)
            SELECT contribution_page_id, financial_type_id, %1, start_date,
            end_date, percentage FROM civicrm_wci_progress_bar_formula WHERE progress_bar_id=%2";

        CRM_Core_DAO::executeQuery($sql,
              array(1=>array($new_pb_id, 'Integer'),
                    2=>array($id, 'Integer'),
        ));
      }
      catch (Exception $e) {
        CRM_Core_Session::setStatus(ts('Failed to create Progress bar. ') .
        $e->getMessage(), '', 'error');
        $transaction->rollback();
      }
    }
    elseif ($action & CRM_Core_Action::DELETE) {
      $errorScope = CRM_Core_TemporaryErrorScope::useException();
      try {
        $transaction = new CRM_Core_Transaction();
        $sql = "DELETE FROM civicrm_wci_progress_bar_formula where progress_bar_id = %1";
        $params = array(1 => array($id, 'Integer'));
        CRM_Core_DAO::executeQuery($sql, $params);

        $sql = "DELETE FROM civicrm_wci_progress_bar where id = %1";
        $params = array(1 => array($id, 'Integer'));
        CRM_Core_DAO::executeQuery($sql, $params);
        $transaction->commit();
      }
      catch (Exception $e) {
        $errmgs = $e->getMessage() . ts('. Check whether progressbar is used by any widget or not');
        CRM_Core_Session::setStatus($errmgs, '', 'error');
        $transaction->rollback();
      }
    }
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Progress Bar List'));

    $query = "SELECT * FROM civicrm_wci_progress_bar";
    $params = array();

    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Wci_DAO_ProgressBar');

    while ($dao->fetch()) {
      $con_page[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $con_page[$dao->id]);

      $action = array_sum(array_keys($this->actionLinks()));
      //build the normal action links.
      $con_page[$dao->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(),
        $action, array('id' => $dao->id));
    }

    if (isset($con_page)) {
      $this->assign('rows', $con_page);
    }
    return parent::run();
  }

  function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Progressbar page?');

      self::$_actionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=update&reset=1&id=%%id%%',
          'title' => ts('Update'),
        ),
        CRM_Core_Action::COPY => array(
          'name' => ts('Clone'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=copy&reset=1&id=%%id%%',
          'title' => ts('copy'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete Custom Field'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ),
      );
    }
    return self::$_actionLinks;
  }
}
