file { "${doc_root}/saf" :
  ensure  => link,
  force   => true,
  target  => "/var/www/application/sample/public",
}
