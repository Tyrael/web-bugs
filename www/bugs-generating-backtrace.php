<?php
require_once '../include/prepend.php';

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

response_header('Generating a gdb backtrace');

backtrace_inline_menu('Unix');

?>

<h1>Generating a gdb backtrace</h1>

<h3>Noticing PHP crashes</h3>

There's no absolute way to know that PHP is crashing, but there may
be signs. Typically, if you access a page that is always supposed
to generate output (has a leading HTML block, for example), and
suddenly get "Document contains no data" from your browser, it may
mean that PHP crashes somewhere along the execution of the script.
Another way to tell that PHP is crashing is by looking at the Apache
error logs, and looking for SEGV (Apache 1.2) or Segmentation
Fault (Apache 1.3).

<h3>Important!</h3>
To get a backtrace with correct information you must have
PHP configured with <code>--enable-debug</code>!

<h3>If you don't have a core file yet:</h3>

<ul>
	<li>
		Remove any limits you may have on core dump size from your shell:
		<ul>
			<li>tcsh:  unlimit coredumpsize</li>
			<li>bash/sh:  ulimit -c unlimited</li>
		</ul>
	</li>
	<li>
		Ensure that the directory in which you're running PHP, or the
		PHP-enabled httpd, has write permissions for the user who's running PHP.
	</li>
	<li>
		Cause PHP to crash:
		<ul>
			<li>PHP CGI: Simply run php with the script that crashes it</li>
			<li>PHP Apache Module: Run httpd -X, and access the script that crashes PHP</li>
		</ul>
	</li>
</ul>

<h3>Generic way to get a core on Linux</h3>

<ul>
	<li>
		Set up the core pattern (run this command as <i>root</i>):
		<ul>
			<li>echo "&lt;cores dir&gt;/core-%e.%p" &gt; /proc/sys/kernel/core_pattern</li>
			<li>make sure the directory is writable by PHP</li>
		</ul>
	</li>
	<li>
		Set the ulimit (see above how to do it). 
	</li>
	<li>
		Restart/rerun PHP.
	</li>
</ul>
<p>After that any process crashing in your system, including PHP, will leave 
its core file in the directory you've specified in <i>core_pattern</i>.</p>

<h3>Once you have the core file:</h3>

<ul>
	<li>
		Run gdb with the path to the PHP or PHP-enabled httpd binary, and
		path to the core file. Some examples:
		<ul>
			<li><code>gdb /usr/local/apache/sbin/httpd /usr/local/apache/sbin/core</code></li>
			<li><code>gdb /home/user/dev/php-snaps/sapi/cli/php /home/user/dev/testing/core</code></li>
		</ul>
	</li>
	<li>
		At the gdb prompt, run:
		<ul>
			<li><code>(gdb) bt</code></li>
		</ul>
	</li>
</ul>

<h3>If you can't get a core file:</h3>
<ul>
	<li>
		Run httpd -X under gdb with something like:
		<ul>
			<li><code>gdb /usr/local/apache/sbin/httpd</code></li>
			<li>(gdb) run -X</li>
		</ul>
	</li>
	<li>
		Then use your web browser and access your server to force the crash.
		You should see a gdb prompt appear and some message indicating that
		there was a crash. At this gdb prompt, type:
		<ul>
			<li><code>(gdb) bt</code></li>
		</ul>
		<ul>
			<li>
				or, running from the commandline
				<ul>
					<li>
						gdb /home/user/dev/php-snaps/sapi/cli/php
						<ul>
							<li><code>(gdb) run /path/to/script.php</code></li>
							<li><code>(gdb) bt</code></li>
						</ul>
					</li>
				</ul>
			</li>
		</ul>
	</li>
</ul>

<p>This should generate a backtrace, that you should submit in
the bug report, along with any other details you can give us
about your setup, and offending script.</p>

<h3>Locating which function call caused a segfault:</h3>

<p>You can locate the function call that caused a segfault, easily, with gdb.
First, you need a core file or to generate a segfault under gdb as described
above.</p>

<p>In PHP, each function is executed by an internal function called
<b><code>execute()</code></b> and has its own stack. Each line
generated by the <b><code>bt</code></b> command represents a function call
stack. Typically, you will see several <b><code>execute()</code></b> lines
when you issue <b><code>bt</code></b>. You are interested in the last
<b><code>execute()</code></b> stack (i.e. smallest frame
number). You can move the current working stack with the
<b><code>up</code></b>, <code>down</code> or
<b><code>frame</code></b> commands. Below is an example gdb session that can
be used as a guideline on how to handle your segfault.</p>

<ul>
	<li>Sample gdb session</li>
	<pre><code>
(gdb) bt
#0  0x080ca21b in _efree (ptr=0xbfffdb9b) at zend_alloc.c:240
#1  0x080d691a in _zval_dtor (zvalue=0x8186b94) at zend_variables.c:44
#2  0x080cfab3 in _zval_ptr_dtor (zval_ptr=0xbfffdbfc) at zend_execute_API.c:274
#3  0x080f1cc4 in execute (op_array=0x816c670) at ./zend_execute.c:1605
#4  0x080f1e06 in execute (op_array=0x816c530) at ./zend_execute.c:1638
#5  0x080f1e06 in execute (op_array=0x816c278) at ./zend_execute.c:1638
#6  0x080f1e06 in execute (op_array=0x8166eec) at ./zend_execute.c:1638
#7  0x080d7b93 in zend_execute_scripts (type=8, retval=0x0, file_count=3) at zend.c:810
#8  0x0805ea75 in php_execute_script (primary_file=0xbffff650) at main.c:1310
#9  0x0805cdb3 in main (argc=2, argv=0xbffff6fc) at cgi_main.c:753
#10 0x400c91be in __libc_start_main (main=0x805c580 <main>, argc=2, ubp_av=0xbffff6fc,
               init=0x805b080 <_init>, fini=0x80f67b4 <_fini>, rtld_fini=0x4000ddd0 <_dl_fini>,
               stack_end=0xbffff6ec) at ../sysdeps/generic/libc-start.c:129
(gdb) frame 3
#3  0x080f1cc4 in execute (op_array=0x816c670) at ./zend_execute.c:1605
(gdb) print (char *)(executor_globals.function_state_ptr->function)->common.function_name
$14 = 0x80fa6fa "pg_result_error"
(gdb) print (char *)executor_globals.active_op_array->function_name
$15 = 0x816cfc4 "result_error"
(gdb) print (char *)executor_globals.active_op_array->filename
$16 = 0x816afbc "/home/yohgaki/php/DEV/segfault.php"
(gdb) 
	</code></pre>
</ul>

<p>In this session, frame 3 is the last <b><code>execute()</code></b>
call. The <b><code>frame 3</code></b> command moves the current working stack
to the proper frame.<br>
<b><code>print (char *)(executor_globals.function_state_ptr->function)->common.function_name</code></b><br>
prints the function name. In the sample gdb session, the
<code>pg_result_error()</code> call
is causing the segfault. You can print any internal data that you like,
if you know the internal data structure. Please do not ask how to use gdb
or about the internal data structure. Refer to gdb manual for gdb usage
and to the PHP source for the internal data structure.</p>

<p>You may not see <b><code>execute</code></b> if the segfault happens
without calling any functions.</p>

<?php response_footer();
