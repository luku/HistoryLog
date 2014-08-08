<?php

/**
 * History log report generation form
 *
 * @copyright Copyright 2014 UCSC Library Digital Initiatives
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * History log report generation form class
 * @package     HistoryLog
 *
 */
class HistoryLog_Form_Reports extends Omeka_Form
{



  /**
   * Construct the report generation form.
   *
   *@return void
   */
  public function init()
  {
    parent::init();
    try{
      $this->_registerElements();
    }catch(Exception $e) {
      throw $e;
    }
	
  }

  /**
   * Define the form elements.
   *
   *@return void
   */
  private function _registerElements()
  {

    try{
      $collectionOptions = $this->_getCollectionOptions();
      $userOptions = $this->_getUserOptions();
      $actionOptions = $this->_getActionOptions();
    }catch(Exception $e) {
      throw $e;
    }

    // Collection:
    $this->addElement('select', 'collection', array(
		        'label'         => __('Collection'),
			'description'   => __('The collection whose items\' log information will be retrieved (default: all)'),
			'value'         => '0',
			'order'         => 1,
			'validators'    => array('digits'),
			'required'      => true,
			'multiOptions'       => $collectionOptions
						    )
		      );

    // User(s):
    $this->addElement('select', 'user', array(
		        'label'         => __('User(s)'),
			'description'   => __('All administrator users whose edits will be retrieved (default: all)'),
			'value'         => '0',
			'order'         => 2,
			'validators'    => array('digits'),
			'required'      => true,
			'multiOptions'       => $userOptions
					      )
		      );

    // Actions:
    $this->addElement('select', 'action', array(
						'label'         => __('Action'),
						'description'   => __('Logged curatorial actions to retrieve in this report (default: all)'),
						'value'         => '0',
						'validators'    => array('alnum'),

						'order'         => 3,
						'required'      => true,
						'multiOptions'  => $actionOptions
						)
		      );

    // Dates:
    $this->addElement('text', 'date-start', array(
						  'label'         => __('Start Date:'),
						  'description'   => __('The earliest date from which to retrieve logs'),
						  'value'         => 'YYYY-MM-DD',
						  'order'         => 4,
						  'style'          => '    max-width: 120px;',
						  'required'      => false,
						  'validators'    => array(array("Date",false,array('format'=>'yyyy-mm-dd')))
						  )
		      );
    $this->addElement('text', 'date-end', array(
						'label'         => __('End Date:'),
						'description'   => __('The latest date from which to retrieve logs'),
						'value'         => 'yyyy-mm-dd',
						'order'         => 5,'style'          => '    max-width: 120px;',
						'required'      => false,
						'validators'    => array(array("Date",false,array('format'=>'yyyy-mm-dd')))
						)
		      );

    // Submit:
    $this->addElement('submit', 'submit-view', array(
						     'label' => __('View Log')
						     ));
    $this->addElement('submit', 'submit-download', array(
							 'label' => __('Download Log')
							 ));

    //Display Groups:
    $this->addDisplayGroup(
			   array(
				 'collection',
				 'user',
				 'actions',
				 'date-start',
				 'date-end'
				 ),
			   'fields'
			   );

    $this->addDisplayGroup(
			   array(
				 'submit-view',
				 'submit-download'
				 ), 
			   'submit_buttons',
			   array(
				 'style'=>'clear:left;'
				 )
			   );

  }

  /**
   * Process the data from the form and retrieve the requested log data.
   *
   *@param string $style The style in which to return the data.
   * Accepted values: "html"(default), "JSON"
   *@return string $log Html to display requested log information
   */
  public static function ProcessPost($style="html")
  {

    //$itemID = $_POST[''];
    $log="";
    $action = '%';
    $itemID = '%';
    $collectionID = '%';
    $userID = '%';
    $timeStart = '1900-00-00';
    $timeEnd = '2100-00-00';

    if(isset($_REQUEST['action']))
      {

	if(!empty($_REQUEST['collection']))
	  $collectionID = $_REQUEST['collection'];
	if(!empty($_REQUEST['action']))
	  $action = $_REQUEST['action'];
	if(!empty($_REQUEST['user']))
	  $userID = $_REQUEST['user'];
	if(!empty($_REQUEST['datestart']) && $_REQUEST['datestart'] != "yyyy-mm-dd")
	  $timeStart = $_REQUEST['datestart'];
	if(!empty($_REQUEST['dateend']) && $_REQUEST['dateend'] != "yyyy-mm-dd")
	  $timeEnd = $_REQUEST['dateend'];

	try{
	  $dB = get_db();
	  $query = 'SELECT id,title,itemID,collectionID,userID,type,value,time FROM `$db->ItemHistoryLog` WHERE itemID LIKE "'.$itemID.'" AND collectionID LIKE "'.$collectionID.'" AND type LIKE "'.$action.'" AND userID LIKE "'.$userID.'" AND time > "'.$timeStart.'" AND time < "'.$timeEnd.'" ORDER BY id DESC;';

	  $result = $dB->query($query);
	  $rows = $result->fetchAll();
	}catch(Exception $e){
	  throw $e;
	}

	if($style == 'html')
	  {
	    $logStart = "<table><tr style=\"font-weight:bold\"><td>Item Title</td><td>User</td><td>Action</td><td>Details</td><td>Date</td></tr>";
	    $rowStart = "<tr><td>";
	    $colSep = "</td><td>";
	    $rowEnd = "</td></tr>";
	    $logEnd = "</table>";
	  } else if ($style == "csv")
	  {
	    $logStart = "";
	    $rowStart = "";
	    $colSep = ",";
	    $rowEnd = PHP_EOL;
	    $logEnd = "";
	  }

	$log .= $logStart;
	$flag = false;
	foreach($rows as $row)
	  {
	    //skip items from all but the selected collection
	    //do this in sql when you get a chance
	    //this will be slow for big collections
	    if( $collectionID !== '%' && $collectionID != $row['collectionID'] )
	      continue;
	    
	    $log.= $rowStart;
	    //$log.=self::_getItem($row['itemID']);
	    $log.=$row['title'];
	    $log.=$colSep;
	    $log.=self::_getUser($row['userID']);
	    $log.=$colSep;
	    $log.=self::_getAction($row['type']);
	    $log.=$colSep;
	    $log.=self::_getValue($row['value'],$row['type']);
	    $log.=$colSep;
	    $log.=self::_getDate($row['time']);
	    $log.=$rowEnd;
	    $flag = true;
	  }
	if(!$flag)
	  $log.=$rowStart."No matching logs found".$colSep.$colSep.$colSep.$colSep.$rowEnd;
	$log.=$logEnd;

      }
    return($log);
  }


  /**
   * Retrieve possible log actions as selectable option list
   *
   *@return array $options An associative array of the 
   *logged item event types
   */
  private function _getActionOptions()
  {
    return( 
	   array(
		 '0'=>'All Actions',
		 'created'=>'Create Item',
		 'updated'=>'Modify Item',
		 'exported'=>'Export Item',
		 'deleted'=>'Delete Item'
		 )
	    );
  }

  /**
   * Retrieve Collections as selectable option list
   *
   *@return array $collections An associative array of the 
   *collection IDs and titles
   */
  private function _getCollectionOptions()
  {
      $collectionTable = get_db()->getTable('Collection');
      $options = $collectionTable->findPairsForSelectForm();
      $options[0] = 'All Collections';
    /*
    $collections = get_records('Collection',array(),'0');
    $options = array('0'=>'All Collections');
    foreach ($collections as $collection)
      {
	try{
	  $titles = $collection->getElementTexts('Dublin Core','Title');
	}catch(Exception $e){
	  throw($e);
	}
	if(isset($titles[0]))
	  $title = $titles[0];
	$options[$collection->id]=$title;
      }
    */
    return $options;
    
  }

  /**
   * Retrieve Omeka Admin Users as selectable option list
   *
   *@return array $collections An associative array of the 
   *userIDs and usernames of all omeka users with admin
   *privileges
   */
  private function _getUserOptions()
  {
    $options = array('0'=>'All Users');

    try{
      $users = get_records('User',array('role'=>'super'),'0');
      foreach($users as $user)
	{
	  $options[$user->id]=$user->name." (super user)";
	}
      return($options);

      $users = get_records('User',array('role'=>'admin'),'0');
      foreach($users as $user)
	{
	  $options[$user->id]=$user->name." (administrator)";
	}
      return($options);

      $users = get_records('User',array('role'=>'contributor'),'0');
      foreach($users as $user)
	{
	  $options[$user->id]=$user->name." (contributor)";
	}
    }catch(Exception $e){
      throw($e);
    }

    return($options);
      
  }

  /**
   * Retrieve title of an item by given itemID
   *
   *@param int $itemID The ID of the item
   *@return string $title The Dublin Core title of the item.
   */
  private static function _getItem($itemID)
  {


    $item = get_record_by_id('Item',$itemID);
    if(empty($item))
      throw new Exception('Item #'.$itemID.' not found');
    
    try{
      $titles = $item->getElementTextsByRecord($item->getElement('Dublin Core','Title'));
    }catch(Exception $e) {
      throw $e;
    }

    if(!empty($titles))
      $title = $titles[0];
    else
      $title  = "Untitled";

    return $title;
  }

  /**
   * Retrieve username of an omeka user by user ID
   *
   *@param int $userID The ID of the Omeka user
   *@return string $username The username of the Omeka user
   */
  private static function _getUser($userID)
  {
    $user = get_record_by_id('User',$userID);
    if(empty($user))
      throw new Exception('cannot find user');
    return $user->name;
  }

  /**
   * Retrieve displayable name of an action by its slug
   *
   *@param string $actionSlug All lower case action name from the database
   *@return string $actionSlug User displayable action name
   */
  private static function _getAction($actionSlug)
  {
    switch($actionSlug)
      {
      case 'deleted':
	return('Item Deleted');
      case 'created':
	return('Item Created');
      case 'updated':
	return('Item Modified');
      case 'exported':
	return('Item Exported');
      default:
	return($actionSlug);
      }
  }

  /**
   * Retrieve "value" parameter in user displayable form
   *
   *@param string $encodedValue The "value" parameter 
   *directly from the database
   *@param string $actionSlug the slug of the type of action
   *associated with this value parameter
   *@return string $value The value is human readable form.
   */
  private static function _getValue($encodedValue,$actionSlug)
  {
    //the encoding is different depending on the type of event,
    //so we define different decoding methods for each event type
    switch($actionSlug)
      {
      case 'deleted':
      case 'created':
	return null;
      case 'updated':
	$update = unserialize($encodedValue);
	if(empty($update)) {
		return("File upload/edit");
	}	
	$rv = 'Metadata elements modified: ';
	$flag = false;
	foreach($update as $elementID)
	  {
	    if($flag)
	      $rv.=", ";
	    else
	      $flag = true;
	    $element = get_record_by_id('Element',$elementID);
	    if(empty($element))
	      $rv.="Unrecognized element #".$elementID;
	    else
	      $rv.=$element->name;
	  }
	return $rv;
      case 'exported':
	return('Exported to '.$encodedValue);
      }
      
  }

  /**
   * Format a date in standard form
   *
   *@param string $dateTime The unformatted dateTime
   *@return string $dateTime The formatted dateTime
   */
  private static function _getDate($dateTime)
  {
    //clearly, not yet fully implemented
    return $dateTime;
  }

}
