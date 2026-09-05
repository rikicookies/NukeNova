<?php
declare(strict_types=1);
namespace NovaNuke\Core\System;
use NovaNuke\Core\Config\ConfigRepository;
final class ProductionReadiness
{
 public function __construct(private readonly ConfigRepository$config,private readonly string$root){}
 /** @return list<array{name:string,passed:bool,required:bool,detail:string}> */ public function run():array
 {
  $checks=[];$this->add($checks,'PHP version',version_compare(PHP_VERSION,'8.3.0','>='),true,'Detected '.PHP_VERSION.'.');
  $missing=array_values(array_filter(['pdo','pdo_mysql','json','mbstring','openssl','fileinfo','dom'],fn(string$e)=>!extension_loaded($e)));$this->add($checks,'PHP extensions',$missing===[],true,$missing===[]?'All required extensions are loaded.':'Missing: '.implode(', ',$missing));
  $this->add($checks,'Production environment',$this->config->get('app.environment')==='production',true,'APP_ENV must be production.');$this->add($checks,'Debug disabled',$this->config->get('app.debug')===false,true,'APP_DEBUG must be false.');
  $url=(string)$this->config->get('app.url','');$https=strtolower((string)parse_url($url,PHP_URL_SCHEME))==='https';$this->add($checks,'HTTPS URL',$https,true,'APP_URL must use https://.');$this->add($checks,'Secure session cookie',$this->config->get('session.secure')===true,true,'SESSION_SECURE must be true.');$this->add($checks,'Security headers',$this->config->get('security.headers_enabled')===true,true,'SECURITY_HEADERS_ENABLED must be true.');
  $key=(string)$this->config->get('app.key','');$this->add($checks,'Application key',str_starts_with($key,'base64:')&&strlen($key)>=50,true,'APP_KEY must retain the generated secret.');
  $writable=[];foreach(['storage/cache','storage/logs','storage/sessions','storage/private','public/uploads']as$d)if(!is_dir($this->root.'/'.$d)||!is_writable($this->root.'/'.$d))$writable[]=$d;$this->add($checks,'Writable directories',$writable===[],true,$writable===[]?'Only expected runtime directories were checked.':'Unavailable: '.implode(', ',$writable));
  $this->add($checks,'PHP display errors',filter_var(ini_get('display_errors'),FILTER_VALIDATE_BOOL)!==true,true,'display_errors must be Off for the production PHP handler.');$this->add($checks,'PHP exposure',filter_var(ini_get('expose_php'),FILTER_VALIDATE_BOOL)!==true,false,'Recommended: expose_php=Off.');$this->add($checks,'OPcache',extension_loaded('Zend OPcache')||function_exists('opcache_get_status'),false,'Recommended for production performance.');
  $smtp=(string)$this->config->get('mail.mailer','log')==='smtp';$this->add($checks,'SMTP transport',$smtp,false,$smtp?'SMTP transport selected.':'Log mail is acceptable for testing; configure SMTP before public email workflows.');return$checks;
 }
 public function passed():bool{foreach($this->run()as$c)if($c['required']&&!$c['passed'])return false;return true;}
 /** @param list<array{name:string,passed:bool,required:bool,detail:string}> $checks */ private function add(array&$checks,string$name,bool$passed,bool$required,string$detail):void{$checks[]=compact('name','passed','required','detail');}
}
