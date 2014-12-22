#Certificate

The certificate plugin offers an enhanced support for creating and administrating certificates inside ILIAS.

## Features

* Multiple certificate types with different layouts
* Generate pretty PDF layouts with JasperReports, the world’s most popular open source reporting engine
* Custom placeholders in certificates
* Multiple languages
* Certificates (pdf files) are stored in the ILIAS data directory instead of getting generated dynamically
* Revision of files
* Rendering PDF certificates with the integraded PDF Service in ILIAS (>= 4.4) or with JasperReports

## Installation

This plugin has some dependencies on other plugins and services. 
Please follow the installation guide of the [documentation](/doc/Documentation.pdf?raw=true).

## Documentation

An installation and user guide is available in [the doc/Documentation.pdf](/doc/Documentation.pdf?raw=true) file.

## Patches

The following classes/methods need to be patched in order for the plugin to work correctly. Most likely these patches will be in the ILIAS core one day, which will remove depending on modified core files for this module.

### /Modules/Course/classes/class.ilCourseParticipants.php

Copy whole method or the code blocks between `PATCH START` and `PATCH END`

```php
    public static function _updatePassed($a_obj_id, $a_usr_id, $a_passed, $a_manual = false, $a_no_origin = false)
    {
        global $ilDB, $ilUser;

        // PATCH START
        $throw_event = false;
        // PATCH END
        
        // #11600
        $origin = -1;
        if($a_manual)
        {
            $origin = $ilUser->getId();
        }	
                                    
        $query = "SELECT passed FROM obj_members ".
        "WHERE obj_id = ".$ilDB->quote($a_obj_id,'integer')." ".
        "AND usr_id = ".$ilDB->quote($a_usr_id,'integer');
        $res = $ilDB->query($query);
        if($res->numRows())
        {
            // #9284 - only needs updating when status has changed
            $old = $ilDB->fetchAssoc($res);	
            if((int)$old["passed"] != (int)$a_passed)
            {	
                
                $query = "UPDATE obj_members SET ".
                    "passed = ".$ilDB->quote((int) $a_passed,'integer').", ".
                    "origin = ".$ilDB->quote($origin,'integer').", ".
                    "origin_ts = ".$ilDB->quote(time(),'integer')." ".
                    "WHERE obj_id = ".$ilDB->quote($a_obj_id,'integer')." ".
                    "AND usr_id = ".$ilDB->quote($a_usr_id,'integer');

                // PATCH START
                if($a_passed)
                {
                    $throw_event = true;
                }
                // PATCH END
            }
        }
        else
        {
            // when member is added we should not set any date 
            // see ilObjCourse::checkLPStatusSync()
            if($a_no_origin && !$a_passed)
            {
                $origin = 0;
                $origin_ts = 0;
            }
            else
            {	
                $origin_ts = time();
            }
            
            $query = "INSERT INTO obj_members (passed,obj_id,usr_id,notification,blocked,origin,origin_ts) ".
                "VALUES ( ".
                $ilDB->quote((int) $a_passed,'integer').", ".
                $ilDB->quote($a_obj_id,'integer').", ".
                $ilDB->quote($a_usr_id,'integer').", ".
                $ilDB->quote(0,'integer').", ".
                $ilDB->quote(0,'integer').", ".
                $ilDB->quote($origin,'integer').", ".
                $ilDB->quote($origin_ts,'integer').")";

            // PATCH START
            if($a_passed)
            {
                $throw_event = true;
            }
            // PATCH END
        }
        $res = $ilDB->manipulate($query);

        // PATCH START
        if($throw_event) {
            global $ilAppEventHandler;
            $ilAppEventHandler->raise('Modules/Course',
                'participantHasPassedCourse',
                array('obj_id' => $a_obj_id,
                    'usr_id' => $a_usr_id,
                ));
        }
        // PATCH END

        return true;
    }
```

### /Modules/Course/classes/class.ilObjCourse.php

This Patch is only needed if you want to copy certificate definitions if a course is copied.
It throws an additional event after cloning a course (append the patch at the end of the method).

```php

public function cloneObject($a_target_id,$a_copy_id = 0)
{

  // [..] 
  
  // BEGIN PATCH
  global $ilAppEventHandler;
  $ilAppEventHandler->raise('Modules/Course',
    'copy',
    array('object' => $new_obj, 'cloned_from_object' => $this)
  );
  // END PATCH
  
  return $new_obj;
}
```

## Contact
studer + raimann ag  
Waldeggstrasse 72  
3097 Liebefeld  
Switzerland 

info@studer-raimann.ch  
www.studer-raimann.ch  

