<?php
/*
 * Copyright (c) 2017, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');



//An implementation of Twig_LoaderInterface
//It works with both raw source code and paths to a twig file.
	//If the $name starts with a \n character, we'll treat it as raw code.
	//Otherwise, we'll assume it's a path to a twig file (relative to the CMS_ROOT).
class Zenario_Twig_Loader implements Twig_LoaderInterface {
    
    //public function getSource($name) {
    //	if (substr($name, 0, 1) === "\n") {
    //		return $name;
    //	} else {
	//    	return file_get_contents(CMS_ROOT. $name);
	//    }
    //}

    public function getCacheKey($name) {
    	return $name;
    }
    

    public function getSourceContext($name) {
    	if (substr($name, 0, 1) === "\n") {
	        return new Twig_Source($name, $name);
    	} else {
    		$path = CMS_ROOT. $name;
	        return new Twig_Source(file_get_contents($path), $name, $path);
	    }
    }

    public function isFresh($name, $time) {
    	if (substr($name, 0, 1) === "\n") {
    		return true;
    	} else {
	        return filemtime(CMS_ROOT. $name) <= $time;
	    }
    }
    

    public function exists($name) {
    	if (substr($name, 0, 1) === "\n") {
    		return true;
    	} else {
	        return file_exists(CMS_ROOT. $name);
	    }
    }
}





//A copy of the above that always only works with raw source code
class Zenario_Twig_String_Loader implements Twig_LoaderInterface {
    
    public function getCacheKey($name) {
    	return $name;
    }
    

    public function getSourceContext($name) {
        //return new Twig_Source($name, sha1($name));
        return new Twig_Source($name, $name);
    }

    public function isFresh($name, $time) {
   		return true;
    }

    public function exists($name) {
   		return true;
    }
}


//An implementation of Twig_CacheInterface that saves files to Zenario's cache directory.
//The main reason for the rewrite is so that we use our createCacheDir() function, which has a working garbage collector.
//(Twig doesn't do any garbage collection so old frameworks can clog up the cache/ directory!)
class Zenario_Twig_Cache implements Twig_CacheInterface {
	
	public function generateKey($name, $className) {
		$hash = base16To64(str_replace('__TwigTemplate_', '', $className));
		
		return CMS_ROOT. 'cache/frameworks/'. $hash .'/class.php';
	}

    public function load($key) {
        if (file_exists($key)) {
			touch(dirname($key). '/accessed');
			@include_once $key;
		}
    }

    public function write($key, $content) {
    	
    	//var_dump('writing', $key, $content);
    	
    	
        $dir = basename(dirname($key));
        createCacheDir($dir, 'cache/frameworks', false);
        file_put_contents($key, $content);
        @chmod($key, 0664);
    }

    public function getTimestamp($key) {
        if (!file_exists($key)) {
            return 0;
        }

        return (int) @filemtime($key);
    }
}