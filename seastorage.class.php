<?php
/**
 * SAE���ݴ洢����
*
* @author quanjun
* @version $Id$
* @package sae
*
*/

/**
 * SaeStorage class
* Storage�����ʺ������洢�û��ϴ����ļ�������ͷ�񡢸����ȡ����ʺϴ洢�������ļ�������ҳ���ڵ��õ�JS��CSS�ȣ����䲻�ʺϴ洢׷��д����־��ʹ��Storage����������JS��CSS������־��������Ӱ��ҳ����Ӧ�ٶȡ�����JS��CSSֱ�ӱ��浽����Ŀ¼����־ʹ��sae_debug()������¼��
*
* <code>
* <?php
* $s = new SaeStorage();
* $s->upload( 'example' , 'remote_file.txt' , 'local_file.txt' );
*
* echo $s->read( 'example' , 'thebook') ;
* // will echo 'bookcontent!';
*
* echo $s->getUrl( 'example' , 'thebook' );
* // will echo 'http://appname-example.stor.sinaapp.com/thebook';
*
* ?>
* </code>
*
* ����������ο���
*  - errno: 0         �ɹ�
*  - errno: -2        ���ͳ�ƴ���
*  - errno: -3        Ȩ�޲���
*  - errno: -7        Domain������
*  - errno: -12    �洢���������ش���
*  - errno: -18     �ļ�������
*  - errno: -101    ��������
*  - errno: -102    �洢����������ʧ��
* ע����ʹ��SaeStorage::errmsg()������õ�ǰ������Ϣ��
*
* @package sae
* @author  quanjun
*
*/

class SaeStorage extends SaeObject
{
	/**
	 * �û�accessKey
	 * @var string
	 */
	private $accessKey = '';
	/**
	 * �û�secretKey
	 * @var string
	 */
	private $secretKey = '';
	/**
	 * ���й����еĴ�����Ϣ
	 * @var string
	 */
	private $errMsg = 'success';
	/**
	 * ���й����еĴ������
	 * @var int
	 */
	private $errNum = 0;
	/**
	 * Ӧ����
	 * @var string
	 */
	private $appName = '';
	/**
	 * @var string
	 */
	private $restUrl = '';
	/**
	 * @var string
	 */
	private $filePath= '';
	/**
	 * �ļ�URL������
	 * @var string
	 */
	private $basedomain = 'stor.sinaapp.com';
	/**
	 * CDN URL������
	 * @var string
	 */
	private $cdndomain = 'sae.sinacdn.com';
	/**
	 * ������֧�ֵ����з���
	 * @var array
	 * @ignore
	 */
	protected $_optUrlList = array(
			"uploadfile"=>'?act=uploadfile&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attr=_ATTR_',
			"getdomfilelist"=>'?act=getdomfilelist&ak=_AK_&sk=_SK_&dom=_DOMAIN_&prefix=_PREFIX_&limit=_LIMIT_&skip=_SKIP_',
			"getfileattr"=>'?act=getfileattr&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attrkey=_ATTRKEY_',
			"getfilecontent"=>'?act=getfilecontent&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
			"delfile"=>'?act=delfile&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
			"delfolder"=>'?act=delfolder&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
			"getdomcapacity"=>'?act=getdomcapacity&ak=_AK_&sk=_SK_&dom=_DOMAIN_',
			"setdomattr"=>'?act=setdomattr&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attr=_ATTR_',
			"setfileattr"=>'?act=setfileattr&ak=_AK_&sk=_SK_&dom=_DOMAIN_&attr=_ATTR_',
			"getfilesnum"=>'?act=getfilesnum&ak=_AK_&sk=_SK_&dom=_DOMAIN_&path=_PATH_',
			"getfileslist"=>'?act=getfileslist&ak=_AK_&sk=_SK_&dom=_DOMAIN_&path=_PATH_&limit=_LIMIT_&skip=_SKIP_&fold=_FOLD_',
	);
	/**
	 * ���캯��
	 * $_accessKey��$_secretKey����Ϊ�գ�Ϊ�յ�����¿�����Ϊ�ǹ������ļ�
	 * @param string $_accessKey
	 * @param string $_secretKey
	 * @return void
	 * @author Elmer Zhang
	*/
	public function __construct( $_accessKey='', $_secretKey='' )
	{
		if( $_accessKey== '' ) $_accessKey = SAE_ACCESSKEY;
		if( $_secretKey== '' ) $_secretKey = SAE_SECRETKEY;

		$this->setAuth( $_accessKey, $_secretKey );
	}

	/**
	 * ����key
	 *
	 * ����Ҫ��������APP������ʱʹ��
	 *
	 * @param string $akey
	 * @param string $skey
	 * @return void
	 * @author Elmer Zhang
	 * @ignore
	 */
	public function setAuth( $akey , $skey , $_appName = '' )
	{
		$this->initOptUrlList( $this->_optUrlList);

		if( $_appName == '') {
			$this->appName = $_SERVER[ 'HTTP_APPNAME' ];
		} else {
			$this->appName = $_appName;
		}

		$this->init( $akey, $skey );
	}

	/**
	 * �������й����еĴ�����Ϣ
	 *
	 * @return string
	 * @author Elmer Zhang
	 */
	public function errmsg()
	{
		$ret = $this->errMsg." url(".$this->filePath.")";
		$this->restUrl = '';
		$this->errMsg = 'success!';
		return $ret;
	}

	/**
	 * �������й����еĴ������
	 *
	 * @return int
	 * @author Elmer Zhang
	 */
	public function errno()
	{
		$ret = $this->errNum;
		$this->errNum = 0;
		return $ret;
	}

	/**
	 * ��ȡappname�������ɵ���ʹ��
	 *
	 * @return string
	 * @author lazypeople
	 */
	public function getAppname()
	{
		$ret = $this->appName;
		return $ret;
	}

	/**
	 * ȡ��ͨ��CDN���ʴ洢�ļ���url
	 *
	 * @param string $domain
	 * @param string $filename
	 * @return string
	 * @author Elmer Zhang
	 */
	public function getCDNUrl( $domain, $filename ) {

		// make it full domain
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( SAE_CDN_ENABLED ) {
			$filePath = "http://".$this->appName.'.'.$this->cdndomain . "/.app-stor/$domain/$filename";
		} else {
			$domain = $this->getDom($domain);
			$filePath = "http://".$domain.'.'.$this->basedomain . "/$filename";
		}
		return $filePath;
	}

	/**
	 * ȡ�÷��ʴ洢�ļ���url
	 *
	 * @param string $domain
	 * @param string $filename
	 * @return string
	 * @author Elmer Zhang
	 */
	public function getUrl( $domain, $filename ) {

		// make it full domain
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);
		$domain = $this->getDom($domain);

		$this->filePath = "http://".$domain.'.'.$this->basedomain . "/$filename";
		return $this->filePath;
	}

	private function setUrl( $domain , $filename )
	{
		$domain = trim($domain);
		$filename = trim($filename);

		$this->filePath = "http://".$domain.'.'.$this->basedomain . "/$filename";
	}

	/**
	 * ������д��洢
	 *
	 * ע�⣺�ļ���������е�'/'���ᱻ���˵���
	 *
	 * @param string $domain �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
	 * @param string $destFileName �ļ���
	 * @param string $content �ļ�����,֧�ֶ���������
	 * @param int $size д�볤��,Ĭ��Ϊ������
	 * @param array $attr �ļ����ԣ������õ�������ο� SaeStorage::setFileAttr() ����
	 * @param bool $compress �Ƿ�gzipѹ���������Ϊtrue�����ļ��ᾭ��gzipѹ�����ٴ���Storage������$attr=array('encoding'=>'gzip')����ʹ��
	 * @return string д��ɹ�ʱ���ظ��ļ������ص�ַ�����򷵻�false
	 * @author Elmer Zhang
	 */
	public function write( $domain, $destFileName, $content, $size=-1, $attr=array(), $compress = false )
	{
		$domain = trim($domain);
		$destFileName = $this->formatFilename($destFileName);

		if ( $domain == '' || $destFileName == '' )
		{
			$this->errMsg = 'the value of parameter (domain,destFileName,content) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		if ( $size > -1 )
			$content = substr( $content, 0, $size );

		$srcFileName = tempnam(SAE_TMP_PATH, 'SAE_STOR_UPLOAD');
		if ($compress) {
			file_put_contents("compress.zlib://" . $srcFileName, $content);
		} else {
			file_put_contents($srcFileName, $content);
		}

		$re = $this->upload($domain, $destFileName, $srcFileName, $attr);
		unlink($srcFileName);
		return $re;
	}

	/**
	 * ���ļ��ϴ���洢
	 *
	 * ע�⣺�ļ���������е�'/'���ᱻ���˵���
	 *
	 * @param string $domain �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
	 * @param string $destFileName Ŀ���ļ���
	 * @param string $srcFileName Դ�ļ���
	 * @param array $attr �ļ����ԣ������õ�������ο� SaeStorage::setFileAttr() ����
	 * @param bool $compress �Ƿ�gzipѹ���������Ϊtrue�����ļ��ᾭ��gzipѹ�����ٴ���Storage������$attr=array('encoding'=>'gzip')����ʹ��
	 * @return string д��ɹ�ʱ���ظ��ļ������ص�ַ�����򷵻�false
	 * @author Elmer Zhang
	 */
	public function upload( $domain, $destFileName, $srcFileName, $attr = array(), $compress = false )
	{
		$domain = trim($domain);
		$destFileName = $this->formatFilename($destFileName);

		if ( $domain == '' || $destFileName == '' || $srcFileName == '' )
		{
			$this->errMsg = 'the value of parameter (domain,destFile,srcFileName) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		if ($compress) {
			$srcFileNew = tempnam(SAE_TMP_PATH, 'SAE_STOR_UPLOAD');
			file_put_contents("compress.zlib://" . $srcFileNew, file_get_contents($srcFileName));
			$srcFileName = $srcFileNew;
		}

		$domain = $this->getDom($domain);
		$parseAttr = $this->parseFileAttr($attr);

		$this->setUrl( $domain, $destFileName );

		$urlStr = $this->optUrlList['uploadfile'];
		$urlStr = str_replace( '_DOMAIN_', $domain , $urlStr );
		$urlStr = str_replace( '_ATTR_', urlencode(json_encode($parseAttr)), $urlStr );
		$postData = array( 'srcFile' => "@{$srcFileName}" , 'destfile' => $destFileName);
		$ret = $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr, $postData ) );
		if ( $ret !== false )
			return $this->filePath;
		else
			return false;
	}


	/**
	 * ��ȡָ��domain�µ��ļ����б�
	 *
	 * <code>
	 * <?php
	 * // �г� Domain ������·����photo��ͷ���ļ�
	 * $stor = new SaeStorage();
	 *
	 * $num = 0;
	 * while ( $ret = $stor->getList("test", "photo", 100, $num ) ) {
	 *         foreach($ret as $file) {
	 *             echo "{$file}\n";
	 *             $num ++;
	 *         }
	 * }
	 *
	 * echo "\nTOTAL: {$num} files\n";
	 * ?>
	 * </code>
	 *
	 * @param string $domain    �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
	 * @param string $prefix    ·��ǰ׺
	 * @param int $limit        ��������,���100��,Ĭ��10��
	 * @param int $offset        ��ʼ������limit��offset֮�����Ϊ10000�������˷�Χ�޷��г���
	 * @return array ִ�гɹ�ʱ�����ļ��б����飬���򷵻�false
	 * @author Elmer Zhang
	 */
	public function getList( $domain, $prefix=NULL, $limit=10, $offset = 0 )
	{
		$domain = trim($domain);

		if ( $domain == '' )
		{
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$urlStr = $this->optUrlList['getdomfilelist'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$urlStr = str_replace( '_PREFIX_', urlencode($prefix), $urlStr );
		$urlStr = str_replace( '_LIMIT_', $limit, $urlStr );
		$urlStr = str_replace( '_SKIP_', $offset, $urlStr );

		return $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr ) );
	}

	/**
	 * ��ȡָ��Domain��ָ��Ŀ¼�µ��ļ��б�
	 *
	 * @param string $domain    �洢��
	 * @param string $path        Ŀ¼��ַ
	 * @param int $limit        ���η����������ƣ�Ĭ��100�����1000
	 * @param int $offset        ��ʼ����
	 * @param int $fold            �Ƿ��۵�Ŀ¼
	 * @return array ִ�гɹ�ʱ�����б����򷵻�false
	 * @author Elmer Zhang
	 */
	public function getListByPath( $domain, $path = NULL, $limit = 100, $offset = 0, $fold = true )
	{
		$domain = trim($domain);

		if ( $domain == '' )
		{
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$urlStr = $this->optUrlList['getfileslist'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$urlStr = str_replace( '_PATH_', urlencode($path), $urlStr );
		$urlStr = str_replace( '_LIMIT_', $limit, $urlStr );
		$urlStr = str_replace( '_SKIP_', $offset, $urlStr );
		$urlStr = str_replace( '_FOLD_', intval($fold), $urlStr );

		return $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr ) );
	}

	/**
	 * ��ȡָ��domain�µ��ļ�����
	 *
	 *
	 * @param string $domain    �洢��,�����߹���ƽ̨.storageҳ��ɽ��й���
	 * @param string $path        Ŀ¼
	 * @return array ִ�гɹ�ʱ�����ļ��������򷵻�false
	 * @author Elmer Zhang
	 */
	public function getFilesNum( $domain, $path = NULL )
	{
		$domain = trim($domain);

		if ( $domain == '' )
		{
			$this->errMsg = 'the value of parameter (domain) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$urlStr = $this->optUrlList['getfilesnum'];

		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$urlStr = str_replace( '_PATH_', urlencode($path), $urlStr );

		return $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr ) );
	}

	/**
	 * ��ȡ�ļ�����
	 *
	 * @param string $domain     �洢��
	 * @param string $filename    �ļ���ַ
	 * @param array $attrKey    ����ֵ,�� array("fileName", "length")����attrKeyΪ��ʱ���Թ������鷽ʽ���ظ��ļ����������ԡ�
	 * @return array ִ�гɹ������鷽ʽ�����ļ����ԣ����򷵻�false
	 * @author Elmer Zhang
	 */
	public function getAttr( $domain, $filename, $attrKey=array() )
	{
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( $domain == '' || $filename == '' )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$this->setUrl( $domain, $filename );

		$urlStr = $this->optUrlList['getfileattr'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$urlStr = str_replace( '_ATTRKEY_', urlencode( json_encode( $attrKey ) ), $urlStr );
		$postData = array( 'filename' => $filename);
		$ret = $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr, $postData ) );
		if ( is_object( $ret ) )
			return (array)$ret;
		else
			return $ret;
	}

	/**
	 * ����ļ��Ƿ����
	 *
	 * @param string $domain     �洢��
	 * @param string $filename     �ļ���ַ
	 * @return bool
	 * @author Elmer Zhang
	 */
	public function fileExists( $domain, $filename )
	{
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( $domain == '' || $filename == '' )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		if ( $this->getAttr( $domain, $filename, array('length') ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * ��ȡ�ļ�������
	 *
	 * @param string $domain
	 * @param string $filename
	 * @return string �ɹ�ʱ�����ļ����ݣ����򷵻�false
	 * @author Elmer Zhang
	 */
	public function read( $domain, $filename )
	{
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( $domain == '' || $filename == '' )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$this->setUrl( $domain, $filename );
		$urlStr = $this->optUrlList['getfilecontent'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );

		$postData = array( 'filename' => $filename);
		$ret =  $this->getJsonContentsAndDecode( $urlStr, $postData, false );
		if ( is_array($ret) && isset( $ret['errno'] ) )
		{
			$this->parseRetData( $ret );
			return false;
		}
		return $ret;
	}

	/**
	 * ɾ��Ŀ¼
	 *
	 * @param string $domain    �洢��
	 * @param string $path      Ŀ¼��ַ
	 * @return bool
	 * @author Elmer Zhang
	 * @ignore
	 */
	public function deleteFolder( $domain, $path )
	{
		$domain = trim($domain);
		$path = $this->formatFilename($path);

		if ( $domain == '' || $path == '' )
		{
			$this->errMsg = 'the value of parameter (domain,path) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$this->setUrl( $domain, $path );
		$urlStr = $this->optUrlList['delfolder'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$postData = array( 'path' => $path);
		$ret = $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr, $postData ) );
		if ( $ret === false )
			return false;
		if ( $ret[ 'errno' ] == 0 )
			return true;
		else
			return false;
	}

	/**
	 * ɾ���ļ�
	 *
	 * @param string $domain
	 * @param string $filename
	 * @return bool
	 * @author Elmer Zhang
	 */
	public function delete( $domain, $filename )
	{
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( $domain == '' || $filename == '' )
		{
			$this->errMsg = 'the value of parameter (domain,filename) can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$this->setUrl( $domain, $filename );
		$urlStr = $this->optUrlList['delfile'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$postData = array( 'filename' => $filename);
		$ret = $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr, $postData ) );
		if ( $ret === false )
			return false;
		if ( $ret[ 'errno' ] == 0 )
			return true;
		else
			return false;
	}


	/**
	 * �����ļ�����
	 *
	 * Ŀǰ֧�ֵ��ļ�����
	 *  - expires: ��������泬ʱ,���ù����domain expires�Ĺ���һ��
	 *  - encoding: ����ͨ��Webֱ�ӷ����ļ�ʱ��Header�е�Content-Encoding��
	 *  - type: ����ͨ��Webֱ�ӷ����ļ�ʱ��Header�е�Content-Type��
	 *  - private: �����ļ�Ϊ˽�У����ļ����ɱ����ء�
	 *
	 * <code>
	 * <?php
	 * $stor = new SaeStorage();
	 *
	 * $attr = array('expires' => 'modified 1y');
	 * $ret = $stor->setFileAttr("test", "test.txt", $attr);
	 * if ($ret === false) {
	 *         var_dump($stor->errno(), $stor->errmsg());
	 * }
	 * ?>
	 * </code>
	 *
	 * @param string $domain
	 * @param string $filename     �ļ���
	 * @param array $attr         �ļ����ԡ���ʽ��array('attr0'=>'value0', 'attr1'=>'value1', ......);
	 * @return bool
	 * @author Elmer Zhang
	 */
	public function setFileAttr( $domain, $filename, $attr = array() )
	{
		$domain = trim($domain);
		$filename = $this->formatFilename($filename);

		if ( $domain == '' || $filename == '' )
		{
			$this->errMsg = 'the value of parameter domain,filename can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$parseAttr = $this->parseFileAttr($attr);
		if ($parseAttr == false) {
			$this->errMsg = 'the value of parameter attr must be an array, and can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$urlStr = $this->optUrlList['setfileattr'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$urlStr = str_replace( '_ATTR_', urlencode( json_encode( $parseAttr ) ), $urlStr );
		$postData = array( 'filename' => $filename);
		$ret = $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr, $postData ) );
		if ( $ret === true )
			return true;
		if ( is_array($ret) && $ret[ 'errno' ] === 0 )
			return true;
		else
			return false;
	}

	/**
	 * ����Domain����
	 *
	 * Ŀǰ֧�ֵ�Domain����
	 *  - expires: ��������泬ʱ
	 *  - expires_type: ���������ָ���ļ����͵Ļ��泬ʱ
	 *  - allowReferer: ����Referer������
	 *  - private: �Ƿ�˽��Domain
	 *  - 404Redirect: 404��תҳ�棬ֻ���Ǳ�Ӧ��ҳ�棬��Ӧ��Storage���ļ�������http://appname.sinaapp.com/404.html��http://appname-domain.stor.sinaapp.com/404.png
	 *  - tag: Domain��顣��ʽ��array('tag1', 'tag2')
	 * <code>
	 * <?php
	 * // �����������
	 * $expires = 'modified 1d';
	 * $expires_type = 'text/html 48h,image/png modified 1y';
	 *
	 * // ����������
	 * $allowReferer = array();
	 * $allowReferer['hosts'][] = '*.elmerzhang.com';        // ������ʵ���Դ������ǧ��Ҫ�� http://��֧��ͨ���*��?
	 * $allowReferer['hosts'][] = 'elmer.sinaapp.com';
	 * $allowReferer['hosts'][] = '?.elmer.sinaapp.com';
	 * $allowReferer['redirect'] = 'http://elmer.sinaapp.com/';    // ����ʱ��ת���ĵ�ַ����������ת����APP��ҳ�棬�Ҳ���ʹ�ö�����������������û������ô�����ֱ�Ӿܾ����ʡ�
	 * //$allowReferer = false;  // ���Ҫ�ر�һ��Domain�ķ��������ܣ�ֱ�ӽ�allowReferer����Ϊfalse����
	 *
	 * $stor = new SaeStorage();
	 *
	 * $attr = array('expires'=>$expires,'expires_type'=>$expires_type,'allowReferer'=>$allowReferer);
	 * $ret = $stor->setDomainAttr("test", $attr);
	 * if ($ret === false) {
	 *         var_dump($stor->errno(), $stor->errmsg());
	 * }
	 *
	 * ?>
	 * </code>
	 *
	 * @param string $domain
	 * @param array $attr         Domain���ԡ���ʽ��array('attr0'=>'value0', 'attr1'=>'value1', ......);
	 *  ˵����
	 *   - expires ��ʽ��[modified] TIME_DELTA������modified 1y����1y��modified�ؼ�������ָ��expireʱ��������ļ����޸�ʱ�䡣Ĭ��expireʱ���������access time�����TIME_DELTAΪ���� Cache-Control header�ᱻ����Ϊno-cache��
	 *   - TIME_DELTA��TIME_DELTA��һ����ʾʱ����ַ��������磺 1y3M 48d 5s
	 *   <pre>
	 *   s   seconds
	 *   ----------------------------------------------------------
	 *   m   minutes
	 *   ----------------------------------------------------------
	 *   h   hours
	 *   ----------------------------------------------------------
	 *   d   days
	 *   ----------------------------------------------------------
	 *   w   weeks
	 *   ----------------------------------------------------------
	 *   M   months, 30 days
	 *   ----------------------------------------------------------
	 *   y   years, 365 days
	 *   ----------------------------------------------------------
	 *   </pre>
	 *   - ���������TIME_DELTA��<pre>
	 *   epoch sets the Expires header to 1 January, 1970 00:00:01 GMT.
	 *   -----------------------------------------------------------------------------------------------
	 *   max sets the Expires header to 31 December 2037 23:59:59 GMT, and the Cache-Control max-age to 10 years.
	 *   -----------------------------------------------------------------------------------------------
	 *   </pre>
	 *   - expires_type ��ʽ:TYPE [modified] TIME_DELTA,TYPEΪ�ļ���mimetype������text/html, text/plain, image/gif������expires-type����֮���� , ���������磺text/html 48h,image/png modified 1y
	 * @return bool
	 * @author Elmer Zhang,Lazypeople
	 */
	public function setDomainAttr( $domain, $attr = array() )
	{
		$domain = trim($domain);

		if ( $domain == '' )
		{
			$this->errMsg = 'the value of parameter domain can not be empty!';
			$this->errNum = -101;
			return false;
		}

		// make it full domain
		$domain = $this->getDom($domain);

		$parseAttr = $this->parseDomainAttr($attr);

		if ($parseAttr == false) {
			$this->errMsg = 'the value of parameter attr must be an array, and can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$urlStr = $this->optUrlList['setdomattr'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$urlStr = str_replace( '_ATTR_', urlencode( json_encode( $parseAttr ) ), $urlStr );
		$ret = $this->parseRetData( $this->getJsonContentsAndDecode( $urlStr ) );
		if ( $ret === true )
			return true;
		if ( is_array($ret) && $ret['errno'] === 0 )
			return true;
		else
			return false;
	}

	/**
	 * ��ȡdomain��ռ�洢�Ĵ�С
	 *
	 * @param string $domain
	 * @return int
	 * @author Elmer Zhang
	 */
	public function getDomainCapacity( $domain )
	{
		$domain = trim($domain);

		if ( $domain == '' )
		{
			$this->errMsg = 'the value of parameter \'$domain\' can not be empty!';
			$this->errNum = -101;
			return false;
		}

		$domain = $this->getDom($domain);

		$urlStr = $this->optUrlList['getdomcapacity'];
		$urlStr = str_replace( '_DOMAIN_', $domain, $urlStr );
		$ret = (array)$this->parseRetData( $this->getJsonContentsAndDecode( $urlStr ) );
		if ( $ret[ 'errno' ] == 0 )
			return $ret['data'];
		else
			return false;
	}

	// =================================================================

	/**
	 * @ignore
	 */
	protected function parseDomainAttr($attr) {
		$parseAttr = array();

		if ( !is_array( $attr ) || empty( $attr ) ) {
			return false;
		}

		foreach ( $attr as $k => $a ) {
			switch ( strtolower( $k ) ) {
				case '404redirect':
					if ( !empty($a) && is_string($a) ) {
						$parseAttr['404Redirect'] = trim($a);
					}
					break;
				case 'private':
					$parseAttr['private'] = $a ? true : false;
					break;
				case 'expires':
					$parseAttr['expires'] = $a;
					break;
				case 'expires_type':
					$parseAttr['expires_type'] = $a;
					break;
				case 'allowreferer':
					if ( isset($a['hosts']) && is_array($a['hosts']) && !empty($a['hosts']) ) {
						$parseAttr['allowReferer'] = array();
						$parseAttr['allowReferer']['hosts'] = $a['hosts'];

						if ( isset($a['redirect']) && is_string($a['redirect']) ) {
							$parseAttr['allowReferer']['redirect'] = $a['redirect'];
						}
					} else {
						$parseAttr['allowReferer']['host'] = false;
					}
					break;
				case 'tag':
					if (is_array($a) && !empty($a)) {
						$parseAttr['tag'] = array();
						foreach ($a as $v) {
							$v = trim($v);
							if (is_string($v) && !empty($v)) {
								$parseAttr['tag'][] = $v;
							}
						}
					}
					break;
				default:
					break;
			}
		}

		return $parseAttr;
	}

	/**
	 * @ignore
	 */
	protected function parseFileAttr($attr) {
		$parseAttr = array();

		if ( !is_array( $attr ) || empty( $attr ) ) {
			return false;
		}

		foreach ( $attr as $k => $a ) {
			switch ( strtolower( $k ) ) {
				case 'expires':
					$parseAttr['expires'] = $a;
					break;
				case 'encoding':
					$parseAttr['encoding'] = $a;
					break;
				case 'type':
					$parseAttr['type'] = $a;
					break;
				case 'private':
					$parseAttr['private'] = intval($a);
					break;
				default:
					break;
			}
		}

		return $parseAttr;
	}

	/**
	 * @ignore
	 */
	protected function initOptUrlList( $_optUrlList=array() )
	{
		$this->optUrlList = array();
		$this->optUrlList = $_optUrlList;

		while ( current( $this->optUrlList ) !== false ) {
			$this->optUrlList[ key( $this->optUrlList ) ] = SAE_STOREHOST.current($this->optUrlList);
			next( $this->optUrlList );
		}

		reset( $this->optUrlList );
		//$this->init( $this->accessKey, $this->secretKey );



	}
	/**
	 * ���캯������ʱ�滻����$this->optUrlListֵ���accessKey��secretKey
	 * @param string $_accessKey
	 * @param string $_secretKey
	 * @return void
	 * @ignore
	 */
	protected function init( $_accessKey, $_secretKey )
	{
		$_accessKey = trim($_accessKey);
		$_secretKey = trim($_secretKey);

		//$this->appName = $_SERVER[ 'HTTP_APPNAME' ];
		$this->accessKey = $_accessKey;
		$this->secretKey = $_secretKey;
		while ( current( $this->optUrlList ) !== false )
		{
			$this->optUrlList[ key( $this->optUrlList ) ] = str_replace( '_AK_', $this->accessKey, current( $this->optUrlList ) );
			$this->optUrlList[ key( $this->optUrlList ) ]= str_replace( '_SK_', $this->secretKey, current( $this->optUrlList ) );
			next( $this->optUrlList );
		}


		reset( $this->optUrlList );
	}

	/**
	 * ���յ���server�˷�����rest������װ
	 * @ignore
	 */
	protected function getJsonContentsAndDecode( $url, $postData = array(), $decode = true ) //��ȡ��ӦURL��JSON��ʽ���ݲ�����
	{
		if( empty( $url ) )
			return false;
		$this->restUrl = $url;
		$ch=curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HTTPGET, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );


		if ( !Empty( $postData ) )
		{
			curl_setopt($ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $postData );
		}


		curl_setopt( $ch, CURLOPT_USERAGENT, 'SAE Online Platform' );
		$content=curl_exec( $ch );
		$info = curl_getinfo($ch);
		//var_dump($content, $info);
		curl_close($ch);
		if( false !== $content )
		{
			if ($decode) {
				$tmp = json_decode( $content , true);

				if ( !empty( $tmp ) )//���ǽṹ������ֱ���׳�����Դ
					return $tmp;
			}
			return $content;
		}
		else
			return array( 'errno'=>-102, 'errmsg'=>'bad request' );
	}

	/**
	 * ��������֤server�˷��ص����ݽṹ
	 * @ignore
	 */
	public function parseRetData( $retData = array() )
	{
		//    print_r( $retData );
		if ( !isset( $retData['errno'] ) || !isset( $retData['errmsg'] ) )
		{
			//    print_r( $retData );
			$this->errMsg = 'bad request';
			$this->errNum = -12;
			return false;
		}
		if ( $retData['errno'] !== 0 )
		{
			$this->errMsg = $retData[ 'errmsg' ];
			$this->errNum = $retData['errno'];
			return false;
		}
		if ( isset( $retData['data'] ) )
			return $retData['data'];
		return $retData;
	}

	/**
	 * domainƴ��
	 * @param string $domain
	 * @param bool $concat
	 * @return string
	 * @author Elmer Zhang
	 * @ignore
	 */
	protected function getDom($domain, $concat = true) {
		$domain = strtolower(trim($domain));

		if ($concat) {
			if( strpos($domain, '-') === false ) {
				$domain = $this->appName .'-'. $domain;
			}
		} else {
			if ( ( $pos = strpos($domain, '-') ) !== false ) {
				$domain = substr($domain, $pos + 1);
			}
		}
		return $domain;
	}

	private function formatFilename($filename) {
		$filename = trim($filename);

		$encodings = array( 'UTF-8', 'GBK', 'BIG5' );

		$charset = mb_detect_encoding( $filename , $encodings);
		if ( $charset !='UTF-8' ) {
			$filename = mb_convert_encoding( $filename, "UTF-8", $charset);
		}

		$filename = preg_replace('/\/\.\//', '/', $filename);
		$filename = ltrim($filename, '/');
		$filename = preg_replace('/^\.\//', '', $filename);
		while ( preg_match('/\/\//', $filename) ) {
			$filename = preg_replace('/\/\//', '/', $filename);
		}

		return $filename;
	}
}
?>