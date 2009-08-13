<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'lang.Process', 
    'lang.Runnable', 
    'lang.RuntimeError', 
    'lang.RuntimeOptions', 
    'lang.ElementNotFoundException'
  );

  /**
   * Represents the runtime - that is, the PHP binary executing the
   * current process.
   *
   * @test     xp://net.xp_framework.unittest.core.RuntimeTest
   * @purpose  Access to PHP runtime
   */
  class Runtime extends Object {
    protected static 
      $instance   = NULL;
      
    protected
      $executable = NULL,
      $startup    = NULL;
    
    static function __static() {
      self::$instance= new self();
      
      // PHP versions < 5.2.1 only have these memory functions if
      // compiled with --enable-memory-limit.
      if (!function_exists('memory_get_usage')) { 
        function memory_get_usage($real) { return -1; } 
        function memory_get_peak_usage($real) { return -1; } 
      }
    }
    
    /**
     * Retrieve the runtime instance
     *
     * @return  lang.Runtime
     */
    public static function getInstance() {
      return self::$instance;
    }
    
    /**
     * Loads a dynamic library.
     *
     * @see     php://dl
     * @param   string name
     * @return  bool TRUE if the library was loaded, FALSE if it was already loaded
     * @throws  lang.IllegalAccessException in case library loading is prohibited
     * @throws  lang.ElementNotFoundException in case the library does not exist
     * @throws  lang.RuntimeError in case dl() fails
     */
    public function loadLibrary($name) {
      if (extension_loaded($name)) return FALSE;
    
      // dl() will fatal if any of these are set - prevent this
      if (!(bool)ini_get('enable_dl') || (bool)ini_get('safe_mode')) {
        throw new IllegalAccessException(sprintf(
          'Loading libraries not permitted by system configuration [enable_dl= %s, safe_mode= %s]',
          ini_get('enable_dl'),
          ini_get('safe_mode')
        ));
      }
      
      // Qualify filename
      $path= rtrim(realpath(ini_get('extension_dir')), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
      $filename= $name.'.'.PHP_SHLIB_SUFFIX;
      
      // Try php_<name>.<ext>, <name>.<ext>      
      if (file_exists($lib= $path.'php_'.$filename)) {
        // E.g. php_sybase_ct.dll
      } else if (file_exists($lib= $path.$filename)) {
        // E.g. sybase_ct.so
      } else {
        throw new ElementNotFoundException('Cannot find library "'.$name.'" in "'.$path.'"');
      }
      
      // Found library, try to load it. dl() expects given argument to not contain
      // a path and will fail with "Temporary module name should contain only 
      // filename" if it does.
      if (!dl(basename($lib))) {
        throw new RuntimeError('dl() failed for '.$lib);
      }
      return TRUE;
    }

    /**
     * Returns the total amount of memory available to the runtime. If there
     * is no limit zero will be returned.
     *
     * @return  int bytes
     */
    public function memoryLimit() {
      return (int)ini_get('memory_limit');
    }

    /**
     * Returns the amount of memory currently allocated by the runtime
     *
     * @see     php://memory_get_peak_usage
     * @return  int bytes
     */
    public function memoryUsage() {
      return memory_get_usage(TRUE);
    }

    /**
     * Returns the peak of of memory that has been allocated by the
     * runtime up until now.
     *
     * @see     php://memory_get_usage
     * @return  int bytes
     */
    public function peakMemoryUsage() {
      return memory_get_peak_usage(TRUE);
    }

    /**
     * Check whether a given extension is available
     *
     * @see     php://extension_loaded
     * @param   string name
     * @return  bool
     */
    public function extensionAvailable($name) {
      return extension_loaded($name);
    }

    /**
     * Register a shutdown hook - a piece of code that will be run before
     * the runtime shuts down (e.g. with exit).
     *
     * @see     php://register_shutdown_function
     * @param   lang.Runnable r
     * @return  lang.Runnable the given runnable
     */
    public function addShutdownHook(Runnable $r) {
      register_shutdown_function(array($r, 'run'));
      return $r;
    }

    /**
     * Parse command line, stopping at first argument without "-"
     * or at "--" (php [options] -- [args...])
     *
     * @param   string[] arguments
     * @return  array<string, var>
     * @throws  lang.FormatException in case an unrecognized argument is encountered
     */
    public static function parseArguments($arguments) {
      $return= array('options' => new RuntimeOptions(), 'bootstrap' => NULL, 'main' => NULL);
      while (NULL !== ($argument= array_shift($arguments))) {
        if ('-' !== $argument{0}) {
          $return['bootstrap']= trim($argument, '"\'');;
          break;
        } else if ('--' === $argument) {
          $return['bootstrap']= trim(array_shift($arguments), '"\'');
          break;
        }
        switch ($argument{1}) {
          case 'q':     // quiet
          case 'n':     // No php.ini file will be used
          case 'C': {   // [cgi] Do not chdir to the script's directory
            $return['options']->withSwitch($argument{1});
            break;
          }

          case 'd': {
            sscanf($argument, "-d%[^=]=%[^\r]", $setting, $value); 
            $setting= ltrim($setting, ' ');
            $return['options']->withSetting($setting, $value, TRUE);
            break;
          }

          default: {
            throw new FormatException('Unrecognized argument "'.$argument.'"');
          }
        }
      }
      if ($main= array_shift($arguments)) {
        $return['main']= XPClass::forName($main);
      }
      return $return;
    }
    
    /**
     * Get startup options
     *
     * @return  lang.RuntimeOptions
     */
    public function startupOptions() {
      if (NULL === $this->startup) {        // Lazy-init
        $this->startup= self::parseArguments($this->getExecutable()->getArguments());
      }
      return clone $this->startup['options'];
    }

    /**
     * Get bootstrap script's filename
     *
     * @param   string which default NULL
     * @return  string
     */
    public function bootstrapScript($which= NULL) {
      if (NULL === $this->startup) {        // Lazy-init
        $this->startup= self::parseArguments($this->getExecutable()->getArguments());
      }
      if ($which) {
        return dirname($this->startup['bootstrap']).DIRECTORY_SEPARATOR.$which.'.php';
      }
      return $this->startup['bootstrap'];
    }

    /**
     * Get entry point class
     *
     * @return  lang.XPClass
     */
    public function mainClass() {
      if (NULL === $this->startup) {        // Lazy-init
        $this->startup= self::parseArguments($this->getExecutable()->getArguments());
      }
      return $this->startup['main'];
    }

    /**
     * Retrieve the executable associated with this runtime.
     *
     * @return  string
     */
    public function getExecutable() {
      if (NULL === $this->executable) {     // Lazy-init
        $this->executable= Process::getProcessById(getmypid(), getenv('XP_RT'));
      }
      return $this->executable;
    }
  }
?>
