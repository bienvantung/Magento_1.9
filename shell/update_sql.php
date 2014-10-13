<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08/10/2014
 * Time: 09:23
 */

require_once 'abstract.php';
require_once dirname(__FILE__).'/../app/Mage.php';

class PHP_Shell_Update extends Mage_Shell_Abstract {
    public function run(){
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $writeConnection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName();
    }
} 