<?php 

if(!class_exists('SeedObject')) {
	define('INC_FROM_DOLIBARR', true);
	require __DIR__.'/../config.php';
	
}

class AssetOFAmounts extends SeedObject {
	
	public $element = 'assetof_amounts';
	
	public $table_element= 'assetof_amounts';
	
	public $childtables=array();
	
	function __construct(&$db)
	{
		$this->db = $db;
		
		$this->fields=array(
				'amount_estimated'=>array('type'=>'float')
				,'amount_real'=>array('type'=>'float')
				,'amount_diff'=>array('type'=>'float')
				,'date'=>array('type'=>'date')
		);
		
		$this->init();
	
	}
	
	function stockCurrentAmount() {
	
		global $user;
		
		$db = & $this->db;
		
		$db->query("DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE date LIKE '".date('Y-m-d')."%'");
		
		$res = $db->query("SELECT SUM(total_estimated_cost) as amount_estimated
					,SUM(total_cost) as amount_real
					,SUM(total_estimated_cost - total_cost) as amount_diff

			FROM ".MAIN_DB_PREFIX."assetOf
			WHERE status='OPEN'");
		
		$obj = $db->fetch_object($res);
		
		$this->amount_estimated = $obj->amount_estimated;
		$this->amount_real= $obj->amount_real;
		$this->amount_diff= $obj->amount_diff;
		
		$this->date= time();
		
		$this->create($user);
		
	}
	
}