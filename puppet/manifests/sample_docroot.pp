file { "${doc_root}" :
  ensure  => directory,
  mode  => '0755',
  require => Package['httpd'],
  notify  => Service['httpd'],
}

file { "${doc_root}/index.html" :
  ensure  => file,
  mode  => '0755',
  require => File["${doc_root}"],
  content => "<html>
<head><title>Puppet Setup Worked</title></head>
<body>
<script src=\"https://cdn.lib.ncsu.edu/website-assets/header/${lib_header_version}/header.js\" id=\"ncsu-lib-header\"></script>
<div style=\"padding:2em;font-family: 'UniversLight', sans-serif;\">
<p>This is a puppet managed server. See the 
<a href=\"/info.php\">PHP Configuration</a></p>
</div>
<script src=\"https://cdn.lib.ncsu.edu/website-assets/footer/${lib_footer_version}/footer.js\" id=\"ncsu-lib-footer\"></script>
</body>
</html>",
}

file { "${doc_root}/info.php" :
  ensure => file,
  mode  => '0755',
  source => "${puppet_files}/info.php",
  require => File["${doc_root}"],
}
