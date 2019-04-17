##
# PHP 7.2

package { 'rh-php72' :
  ensure  => present,
  require => Exec['yum update -y'],
}
package { 'rh-php72-php' :
  ensure  => present,
  require => Package['rh-php72'],
}
package { 'rh-php72-php-ldap' :
  ensure  => present,
  require => Package['rh-php72-php'],
}
# package { 'rh-php72-php-json' :
#   ensure  => present,
#   require => Package['rh-php72-php'],
# }
package { 'rh-php72-php-mbstring' :
  ensure  => present,
  require => Package['rh-php72-php'],
}
# package { 'rh-php72-php-mcrypt' :
#   ensure  => present,
#   require => Package['rh-php72-php'],
# }
# package { 'rh-php72-php-mssql' :
#   ensure  => present,
#   require => Package['rh-php72-php'],
# }
package { 'rh-php72-php-mysqlnd' :
  ensure  => present,
  require => Package['rh-php72-php'],
}
package { 'rh-php72-php-pdo' :
  ensure  => present,
  require => Package['rh-php72-php'],
}
package { 'rh-php72-php-odbc' :
  ensure  => present,
  require => Package['rh-php72-php'],
}
# package { 'rh-php72-php-oci8' :
#   ensure  => present,
#   require => Package['rh-php72-php'],
# }
package { 'rh-php72-php-pgsql' :
  ensure  => present,
  require => Package['rh-php72-php'],
}
package { 'rh-php72-php-soap' :
  ensure  => present,
  require => Package['rh-php72-php'],
}
package { 'rh-php72-php-xml' :
  ensure  => present,
  require => Package['rh-php72-php'],
}

package { 'httpd24' :
  ensure  => present,
  require => Package['rh-php72-php'],
}

# rhel 72
apache::mod { 'php72' : 
    name => 'librh-php72-php7',
    id => 'php7_module',
    lib => 'librh-php72-php7.so',
    lib_path => '/opt/rh/httpd24/root/usr/lib64/httpd/modules',
    require => Package['rh-php72-php'],
}

file { '/etc/httpd/conf.modules.d/librh-php72-php7.conf' :
  ensure  => file,
  source => "${puppet_files}/php.conf",
  require => Package['rh-php72-php'],
}

file { '/usr/bin/php':
  ensure  => link,
  mode => '0755',
  source => '/opt/rh/rh-php72/root/bin/php',
  require => Package['rh-php72-php'],
}
