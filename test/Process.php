<?php

namespace Minimalism\Test;

use Minimalism\Process;
use Minimalism\ProcessException;

require __DIR__ . "/../src/Process.php";


function testProc()
{
    $fdSpec  = [
        /*others .... */
    ];
    $cwd = $env = null;
    $proc = proc_open("sleep 5", $fdSpec, $pipes, $cwd, $env);
    proc_terminate($proc, SIGTERM); // SIGKILL // 给子进程发信号
    // proc_get_status($proc);
    $exitCode = proc_close($proc); // !! 无法终止子进程执行, 会阻塞
    echo "hello\n";
}

function testStatus()
{
    $proc = new Process("php", null, "<?php echo 'hello world';");

    assert($proc->start() === true);

//    assert($proc->kill(SIGTERM) === true); sleep(1);

    if ($proc->getResult($out, $err)) {
        var_dump($out, $err);
    } else {
        var_dump($proc->getExitCode());
        var_dump($proc->getTermSig());
    }

    echo "\n\n";
}

function testExecute()
{
    try {
        Process::exec("sleep 100", null, null, 0, 100 * 1000);
        assert(false);
    } catch (\Exception $ex) {
        assert($ex instanceof ProcessException);
    }

    try {
        Process::exec("sleep", 100, null,  0, 100 * 1000);
        assert(false);
    } catch (\Exception $ex) {
        assert($ex instanceof ProcessException);
    }


    list($out, $err) = Process::exec("php", "-r \"echo 'hello world';\"");
    var_dump($out, $err);
    echo "\n\n";

    $code = "echo 'hello world';";
    list($out, $err) = Process::exec("php", null, "<?php $code");
    var_dump($out, $err);
    echo "\n\n";
}


function testRunPhp()
{
    list($out, $err) = Process::exec("exec", "php", "<?php phpinfo();");
    echo $out;
}

testProc();

testStatus();
testExecute();

testRunPhp();





