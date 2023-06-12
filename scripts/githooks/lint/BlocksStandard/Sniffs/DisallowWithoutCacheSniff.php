<?php

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class Block_Sniffs_DisallowBlocksWithoutCacheSniff implements Sniff
{

    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return array(int)
     */
    public function register()
    {
        return array(T_CLASS);
    }

    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Lookup for cache methods
        $foundCacheMethods = false;
        $loopStackPtr = $stackPtr;
        $loopStackPtr = $phpcsFile->findNext([T_FUNCTION], ($loopStackPtr + 1));
        while ($loopStackPtr !== false && !$foundCacheMethods) {
            if ($tokens[$loopStackPtr]['type'] === "T_FUNCTION") {
                $methodName = $phpcsFile->getDeclarationName($loopStackPtr);
                if (in_array($methodName, ["getCacheContexts", "getCacheTags", "getCacheMaxAge"])) {
                    $foundCacheMethods = true;
                }
            }
            $loopStackPtr = $phpcsFile->findNext([T_FUNCTION], ($loopStackPtr + 1));
        }

        // Bailout if cache methods are present.
        // We don't set an error here because you may implement the cache in the render array somewhere
        // in the build method.
        if ($foundCacheMethods) {
            return;
        }

        // Lookup build method.
        $foundBuildMethod = false;
        $buildMethodStackPtr = null;
        $loopStackPtr = $stackPtr;
        $loopStackPtr = $phpcsFile->findNext([T_FUNCTION], ($loopStackPtr + 1));
        while ($loopStackPtr !== false && !$foundBuildMethod) {
            if ($tokens[$loopStackPtr]['type'] === "T_FUNCTION") {
                $methodName = $phpcsFile->getDeclarationName($loopStackPtr);
                if (in_array($methodName, ["build"])) {
                    $buildMethodStackPtr = $loopStackPtr;
                    $foundBuildMethod = true;
                }
            }
            $loopStackPtr = $phpcsFile->findNext([T_FUNCTION], ($loopStackPtr + 1));
        }

        // Build method.
        $token  = $tokens[$buildMethodStackPtr];
        $start = $token['scope_opener'];
        $end  = $token['scope_closer']; // End of function.

        $foundCacheProperty = false;

        // Look for arrays.
        for ($assign = $start; $assign < $end; $assign++) {
            $nextArray = $phpcsFile->findNext([T_ARRAY, T_OPEN_SHORT_ARRAY], $assign);
            $token = $tokens[$nextArray];
            $code = $token['code'];
            if (!$nextArray) {
                continue;
            }

            if ($code === T_ARRAY || $code === T_OPEN_SHORT_ARRAY) {
                if ($code === T_OPEN_SHORT_ARRAY) {
                    $array_start = $token['bracket_opener'] - 1;
                    $array_end   = $token['bracket_closer'];
                } else if ($code === T_ARRAY) {
                    $array_start = $token['parenthesis_opener'];
                    $array_end   = $token['parenthesis_closer'];
                }

                // Look for #cache property.
                for ($i = $array_start; $i < $array_end; $i++) {
                    // var_dump($tokens[$i]);
                    if ($tokens[$i]['code'] === T_CONSTANT_ENCAPSED_STRING && str_contains($tokens[$i]['content'], '#cache')) {
                        $foundCacheProperty = true;
                    }
                }
            }
        }

     

        if (!$foundCacheProperty) {
            $error = 'No cache implementation found for this block. Add #cache property, or define one of the following methods: getCacheContexts, getCacheTags, getCacheMaxAge. Refer to https://www.drupal.org/docs/8/api/cache-api/cache-api#s-cacheability-metadata for more information';
            $phpcsFile->addError($error, $stackPtr, 'MissingCache');        
        }
    }

}
