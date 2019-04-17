vcsrepo { "${app_vendor_path}/Zend" :
  ensure   => present,
  provider => git,
  force   => true,
  source   => 'git://github.com/zendframework/zendframework.git',
  require => File["${app_vendor_path}"],
}

file { "${app_path}/library/Zend" :
  ensure  => link,
  force   => true,
  target  => "${app_vendor_path}/Zend/library/Zend",
  require => [File["${app_path}/library"], Vcsrepo["${app_vendor_path}/Zend"]],
}

#TODO this one requires composer
#+ composer.pp

exec { 'composer_zend3' :
  command => "/usr/bin/composer install --no-plugins --no-scripts --working-dir=${app_vendor_path}/Zend",
  environment => ["HOME=${app_vendor_path}/Zend/"],
  #TODO run not as root
  path => "${app_vendor_path}/Zend/:/usr/bin",
  require => Vcsrepo["${app_vendor_path}/Zend"],
}
