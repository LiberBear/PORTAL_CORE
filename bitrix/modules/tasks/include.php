<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/lang.php");

global $DBType;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__); // all common phrases place here
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/tasks/tools.php");

CModule::IncludeModule("iblock");

CModule::AddAutoloadClasses(
	'tasks',
	array(
		'CTasks'                 => 'classes/general/task.php',
		'CTaskMembers'           => 'classes/general/taskmembers.php',
		'CTaskTags'              => 'classes/general/tasktags.php',
		'CTaskFiles'             => 'classes/general/taskfiles.php',
		'CTaskDependence'        => 'classes/general/taskdependence.php',
		'CTaskTemplates'         => 'classes/general/tasktemplates.php',
		'CTaskSync'              => 'classes/general/tasksync.php',
		'CTaskReport'            => 'classes/general/taskreport.php',
		'CTasksWebService'       => 'classes/general/taskwebservice.php',
		'CTaskLog'               => 'classes/general/tasklog.php',
		'CTaskNotifications'     => 'classes/general/tasknotifications.php',
		'CTaskElapsedTime'       => 'classes/general/taskelapsed.php',
		'CTaskReminders'         => 'classes/general/taskreminders.php',
		'CTasksReportHelper'     => 'classes/general/tasks_report_helper.php',
		'CTasksNotifySchema'     => 'classes/general/tasks_notify_schema.php',
		'CTasksPullSchema'       => 'classes/general/tasks_notify_schema.php',
		'CTaskComments'          => 'classes/general/taskcomments.php',
		'CTaskFilterCtrl'        => 'classes/general/taskfilterctrl.php',
		'CTaskAssert'            => 'classes/general/taskassert.php',
		'CTaskItemInterface'     => 'classes/general/taskitem.php',
		'CTaskItem'              => 'classes/general/taskitem.php',
		'CTaskPlannerMaintance'  => 'classes/general/taskplannermaintance.php',
		'CTasksRarelyTools'      => 'classes/general/taskrarelytools.php',
		'CTasksTools'            => 'classes/general/tasktools.php',
		'CTaskSubItemAbstract'   => 'classes/general/subtaskitemabstract.php',
		'CTaskCheckListItem'     => 'classes/general/checklistitem.php',
		'CTaskElapsedItem'       => 'classes/general/elapseditem.php',
		'CTaskLogItem'           => 'classes/general/logitem.php',
		'CTaskCommentItem'       => 'classes/general/commentitem.php',
		'CTaskRestService'       => 'classes/general/restservice.php',
		'CTaskListCtrl'          => 'classes/general/tasklistctrl.php',
		'CTaskListState'         => 'classes/general/taskliststate.php',
		'CTaskIntranetTools'     => 'classes/general/intranettools.php',
		'CTaskTimerCore'         => 'classes/general/timercore.php',
		'CTaskTimerManager'      => 'classes/general/timermanager.php',
		'CTaskCountersProcessor' => 'classes/general/countersprocessor.php',
		'CTaskCountersQueue'     => 'classes/general/countersprocessor.php',
		'CTaskCountersProcessorInstaller'   => 'classes/general/countersprocessorinstaller.php',
		'CTaskCountersProcessorHomeostasis' => 'classes/general/countersprocessorhomeostasis.php',
		'CTaskCountersNotifier'             => 'classes/general/countersnotifier.php',
		'CTaskColumnList'                   => 'classes/general/columnmanager.php',
		'CTaskColumnContext'                => 'classes/general/columnmanager.php',
		'CTaskColumnManager'                => 'classes/general/columnmanager.php',
		'CTaskColumnPresetManager'          => 'classes/general/columnmanager.php',
		'Bitrix\Tasks\TaskTable'            => 'lib/task.php',
		'Bitrix\Tasks\ElapsedTimeTable'     => 'lib/elapsedtime.php',
		'Bitrix\Tasks\MemberTable'          => 'lib/member.php',
		'Bitrix\Tasks\TagTable'             => 'lib/tag.php',
		'\Bitrix\Tasks\TaskTable'           => 'lib/task.php',
		'\Bitrix\Tasks\ElapsedTimeTable'    => 'lib/elapsedtime.php',
		'\Bitrix\Tasks\MemberTable'         => 'lib/member.php',
		'\Bitrix\Tasks\TagTable'            => 'lib/tag.php',

		'Bitrix\Tasks\DB\Helper'			=> "lib/db/".ToLower($DBType)."/helper.php",
		'\Bitrix\Tasks\DB\Helper'			=> "lib/db/".ToLower($DBType)."/helper.php",

		'\Bitrix\Tasks\ActionNotAllowedException'				=> "lib/exception.php",
		'\Bitrix\Tasks\ActionFailedException'					=> "lib/exception.php",
		'\Bitrix\Tasks\AccessDeniedException'					=> "lib/exception.php",
		'\Bitrix\Tasks\ActionRestrictedException'				=> "lib/exception.php",

		'\Bitrix\Tasks\DB\Tree\Exception'						=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\NodeNotFoundException'			=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\TargetNodeNotFoundException'		=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\ParentNodeNotFoundException'		=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\LinkExistsException'				=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\LinkNotExistException'			=> "lib/db/tree/exception.php",

		'\Bitrix\Tasks\Dispatcher\EntityNotFoundException'		=> "lib/dispatcher/exception.php",
		'\Bitrix\Tasks\Dispatcher\MethodNotFoundException'		=> "lib/dispatcher/exception.php",
		'\Bitrix\Tasks\Dispatcher\BadQueryException'			=> "lib/dispatcher/exception.php",

		'\Bitrix\Tasks\DB\Helper'								=> "lib/db/".ToLower($DBType)."/helper.php",

		// for compatibility
		'\Bitrix\Tasks\Template\CheckListItemTable'			    => "lib/template/checklist.php",
	)
);

////////////////////////
// assets

// basic asset, contains widely-used phrases and js-stuff required everywhere
CJSCore::RegisterExt(
	'tasks',
	array(
		'js'  => '/bitrix/js/tasks/tasks.js',
		'lang' => BX_ROOT."/modules/tasks/lang/".LANGUAGE_ID."/include.php",
	)
);

// media kit asset, contains sprites and common css used in components
CJSCore::RegisterExt(
	'tasks_media',
	array(
		'css'  => '/bitrix/js/tasks/css/media.css',
	)
);

// util asset, contains fx functions, helper functions and so on
CJSCore::RegisterExt(
	'tasks_util',
	array(
		'js'  => '/bitrix/js/tasks/util.js',
	)
);

// oop asset, contains basic class for making js oop emulation work
CJSCore::RegisterExt(
	'tasks_util_base',
	array(
		'js'  => '/bitrix/js/tasks/util/base.js',
		'rel' =>  array('core')
	)
);

// widget asset, allows to create widget-based js-controls
CJSCore::RegisterExt(
	'tasks_util_widget',
	array(
		'js'  => '/bitrix/js/tasks/util/widget.js',
		'rel' =>  array('tasks_util_base')
	)
);

// asset that implements client-side interface for common ajax api
CJSCore::RegisterExt(
	'tasks_util_query',
	array(
		'js'  => '/bitrix/js/tasks/util/query.js',
		'rel' =>  array('tasks_util_base', 'ajax')
	)
);

// asset that implements templating mechanism
CJSCore::RegisterExt(
	'tasks_util_template',
	array(
		'js'  => '/bitrix/js/tasks/util/template.js',
	)
);

// asset that imports datepicker widget
CJSCore::RegisterExt(
	'tasks_util_datepicker',
	array(
		'js'  => array(
			'/bitrix/js/tasks/util/datepicker.js',
		),
		'rel' =>  array('tasks_util_widget', 'date')
	)
);

// asset that imports an util for implementing drag-n-drop
CJSCore::RegisterExt(
	'tasks_util_draganddrop',
	array(
		'js'  => array(
			'/bitrix/js/tasks/util/draganddrop.js',
		),
		'rel' =>  array('tasks_util_base', 'tasks_util')
	)
);

// asset that imports a list rendering control (abstract)
CJSCore::RegisterExt(
	'tasks_util_itemset',
	array(
		'js'  => array(
			'/bitrix/js/tasks/util/itemset.js',
		),
		'rel' =>  array('tasks_util_widget')
	)
);

// asset that imports a list rendering control different implementations
CJSCore::RegisterExt(
	'tasks_itemsetpicker',
	array(
		'js'  => array(
			'/bitrix/js/tasks/itemsetpicker.js',
		),
		'rel' =>  array('tasks_util_itemset', 'tasks_integration_socialnetwork')
	)
);

// asset that imports js-api for interacting with user day plan
CJSCore::RegisterExt(
	'tasks_dayplan',
	array(
		'js'  => array(
			'/bitrix/js/tasks/dayplan.js',
		),
		'rel' =>  array('tasks_ui_base', 'tasks_util_query')
	)
);

// asset that implements some integration with "socialnetwork" module
CJSCore::RegisterExt(
	'tasks_integration_socialnetwork',
	array(
		'js'  => array(
			'/bitrix/js/tasks/integration/socialnetwork.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util', 'tasks_util_query', 'socnetlogdest')
	)
);

// shared js parts
CJSCore::RegisterExt(
	'tasks_shared_form_projectplan',
	array(
		'js'  => array(
			'/bitrix/js/tasks/shared/form/projectplan.js',
		),
		'rel' =>  array('tasks_util_widget', 'tasks_util_datepicker')
	)
);

// assets for implementing gantt js api
CJSCore::RegisterExt(
	'task_date',
	array(
		'js' => '/bitrix/js/tasks/task-date.js',
	)
);
CJSCore::RegisterExt(
	'task_calendar',
	array(
		'js' => '/bitrix/js/tasks/task-calendar.js',
		'rel' => array('task_date')
	)
);
CJSCore::RegisterExt(
	'gantt',
	array(
		'js' => array(
			'/bitrix/js/tasks/gantt.js',
			'/bitrix/js/main/dd.js'
		),
		'css' => '/bitrix/js/tasks/css/gantt.css',
		'lang' => BX_ROOT.'/modules/tasks/lang/'.LANGUAGE_ID.'/gantt.php',
		'rel' => array('popup', 'date', 'task_info_popup', 'task_calendar', 'task_date')
	)
);

// todo: deprecated assets, remove

CJSCore::RegisterExt(
	'task_info_popup',
	array(
		'js' => '/bitrix/js/tasks/task-info-popup.js',
		'css' => '/bitrix/js/tasks/css/task-info-popup.css',
		'lang' => BX_ROOT.'/modules/tasks/lang/'.LANGUAGE_ID.'/task-info-popup.php',
		'rel' => array('popup', 'tasks_util')
	)
);

CJSCore::RegisterExt(
	'task_popups',
	array(
		'js' => '/bitrix/js/tasks/task-popups.js',
		'css' => '/bitrix/js/tasks/css/task-popups.css',
		'lang' => BX_ROOT.'/modules/tasks/lang/'.LANGUAGE_ID.'/task-popups.php',
		'rel' => array('popup')
	)
);

CJSCore::RegisterExt(
	'CJSTask',
	array(
		'js'  => '/bitrix/js/tasks/cjstask.js',
		'rel' =>  array('ajax', 'json')
	)
);
CJSCore::RegisterExt(
	'taskQuickPopups',
	array(
		'js'  => '/bitrix/js/tasks/task-quick-popups.js',
		'rel' =>  array('popup', 'ajax', 'json', 'CJSTask')
	)
);

$GLOBALS["APPLICATION"]->AddJSKernelInfo(
	'tasks',
	array(
		'/bitrix/js/tasks/cjstask.js', '/bitrix/js/tasks/core_planner_handler.js',
		'/bitrix/js/tasks/task-iframe-popup.js', '/bitrix/js/tasks/task-quick-popups.js'
	)
);

$GLOBALS["APPLICATION"]->AddCSSKernelInfo('tasks', array('/bitrix/js/tasks/css/tasks.css', '/bitrix/js/tasks/css/core_planner_handler.css'));

//CTaskAssert::enableLogging();