<?php
/**
 * System User Model
 * @author Donny
 */
namespace Kuga\Core\User;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;
use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;
use Phalcon\Mvc\Model\Relation;

class UserModel extends AbstractModel {
    const MIN_LENGTH_PWD = 6;
	private $_salt = 'qing';
	/**
	 * User id
	 * @var integer
	 */
	public $uid;
	
	/**
	 * username
	 * @var string
	 */
	public $username;
	
	/**
	 * password
	 * @var string
	 */
	public $password;
	
	/**
	 *
	 * @var string
	 */
	public $mobile;
	
	/**
	 *
	 * @var string
	 */
	public $email;
	

	
	/**
	 *
	 * @var integer
	 */
	public $createTime;
	
	
	
	/**
	 *
	 * @var string
	 */
	public $lastVisitIp;
	
	/**
	 *
	 * @var integer
	 */
	public $lastVisitTime;
	/**
	 * 性别
	 * @var integer
	 */
	public $gender;
	/**
	 * Independent Column Mapping.
	 */
	public function columnMap() {
		return array (
			'uid' => 'uid',
			'username' => 'username',
			'password' => 'password',
			'mobile' => 'mobile',
			'email' => 'email',
			'create_time' => 'createTime',
			'last_visit_ip' => 'lastVisitIp',
			'last_visit_time' => 'lastVisitTime',
			'gender'=>'gender'
		);
	}
	/**
	 * 所属门店名称
	 * @var string
	 */
	public $storeName;
	/**
	 * Validations and business logic
	 */
	public function validation() {
	    $validator = new Validation();
	    $validator->add('username', new PresenceOfValidator([
	        'model'=>$this,
	        'message'=>$this->translator->_('用户名必须填写')
	    ]));
	    $validator->add('username',new UniquenessValidator([
	        'model'=>$this,
	        'message'=>$this->translator->_('用户名已存在')
	    ]));
	    if($this->email){
            $validator->add('email',new EmailValidator([
                'model'=>$this,
                'message'=>$this->translator->_('Email格式错误')
            ]));
        }
	    $validator->add('email',new UniquenessValidator([
	        'model'=>$this,
	        'message'=>$this->translator->_('Email已存在')
	    ]));
	    return $this->validate($validator);
	}
	public function getSource() {
		return 't_user';
	}
	/**
	 * 添加前设置默认值
	 */
	private function _setDefaultData() {
		$this->lastVisitTime || $this->lastVisitTime = time();
		
		$this->token || $this->token = new \Phalcon\Db\RawValue ( 'default' );
	
		$this->lastVisitIp =\Qing\Lib\Utils::getClientIp();

	}
	/**
	 * 设置默认值，否则无法保存前无法通过验证
	 */
	public function beforeValidationOnCreate() {
		$this->_setDefaultData ();
	}
	/**
	 * 取得主键属性名
	 * @return string
	 */
	public function getPrimaryField(){
		return 'uid';
	}
	
	public function initialize() {
		parent::initialize ();
		$this->keepSnapshots(true);
		$this->hasMany("uid", "RoleUserModel", "uid", array('alias'=>'RoleUserModel',
		'foreignKey'=>array('action'=>Relation::ACTION_CASCADE)));
		$this->belongsTo('storeId', 'StoreModel', 'id');
	}
	public function joinFind($cond,$cols=[]){
	    if(empty($cols)){
	        $cols = array('*','`storeId;name`'=>'storeName');
	    }
	    return parent::joinFind($cond,$cols);
	}

    /**
     * 密码加密
     * @param unknown $pwd
     * @return string
     */
    public function passwordHash($pwd){
        if(strlen($pwd)<self::MIN_LENGTH_PWD){
            throw new ModelException($this->translator->nquery('密码至少要有%s%位','密码至少要有%s%位',self::MIN_LENGTH_PWD,['s'=>self::MIN_LENGTH_PWD]));
        }
        return password_hash($pwd,PASSWORD_DEFAULT);
    }

    /**
     * 密码验证
     * @param $hash 密文密码
     * @param $pwd 明文密码
     * @return bool 是否正确
     */
    public function passwordVerify($hash,$pwd){
        return password_verify($pwd,$hash);
    }
	public function beforeCreate(){
	    $this->createTime = time();
	    if(!$this->password){
	        throw new ModelException($this->translator->_('请设置好密码'));
	    }
	    $this->password = $this->passwordHash($this->password);
	    return true;
	}
	public function beforeUpdate(){
	    if($this->password){
    	    if($this->hasSnapshotData() && $this->hasChanged('password')){
    	        $this->password = $this->passwordHash($this->password);
    	    }
	    }else{
	        $this->skipAttributesOnUpdate(['password']);
	    }
	    return true;
	}
	public function afterSave(){
	    $this->getEventsManager()->fire('qing:updateUser', $this);
	}
}
