<?php
/**
 * New setting, customize the default header
 */
$settings->add(new admin_setting_configtext(
            'projectgradeup/Headerconfig',
            get_string('headerconfig', 'block_projectgradeup'),
            get_string('descconfig', 'block_projectgradeup'),
            'Project Grade Up'
        ));
/**
 * New setting, allow or disallow students to view both heatmap types
 */
$settings->add(new admin_setting_configcheckbox(
             'projectgradeup/Allow_Both_HeatMap_Charts',
             get_string('labelallowbothheatmap', 'block_projectgradeup'),
             get_String('labeldisablebothheatmap', 'block_projectgradeup'),
             '0'
));
/**
 * New setting, change the chron time of the block
 */
$settings->add(new admin_setting_configtext(
            'projectgradeup/Change_Default_Cron',
            get_string('change_default_cron', 'block_projectgradeup'),
            get_string('default_cron', 'block_projectgradeup'),
            300,
            PARAM_INT
));
/**
 * New setting, allow the use of the suffixes
 */
$settings->add(new admin_setting_configcheckbox(
            'projectgradeup/Use_Suffix',
            get_string('labelallowuse_suffix', 'block_projectgradeup'),
            get_string('labeldisableuse_suffix', 'block_projectgradeup'),
            '0'
));
/**
 * New setting, allow the use of suffixes at a course level
 */
$settings->add(new admin_setting_configcheckbox(
            'projectgradeup/Use_Course_Lvl_Suffix',
            get_string('labelallowusecourselvl_suffix', 'block_projectgradeup'),
            get_string('labeldisableusecourselvl_suffix', 'block_projectgradeup'),
            '0'
));
/**
 * New setting, allow the use curly braces [NOT RECOMMENDED]
 */
$settings->add(new admin_setting_configcheckbox(
            'projectgradeup/Use_Curly_Braces',
            get_string('labelallowcurlybraces', 'block_projectgradeup'),
            get_string('labeldisablecurlybraces', 'block_projectgradeup'),
            '0'
));
