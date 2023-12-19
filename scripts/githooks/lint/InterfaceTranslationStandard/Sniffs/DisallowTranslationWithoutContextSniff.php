<?php

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class Translation_Sniffs_DisallowTranslationWithoutContextSniff implements Sniff {

  /**
   * Returns the token types that this sniff is interested in.
   *
   * @return array(int)
   */
  public function register()
  {
    return [T_STRING];
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
  public function process(File $phpcsFile, $stackPtr) {
    $currentFile = $phpcsFile->getFilename();
    // Excluded files pattern.
    $patterns = [
      '#modules/.+/src/Form/#',
      '#modules/.+/src/Element/#',
      '#modules/.+/src/Routing/#',
      '#modules/.+/src/Annotation/#',
      '#modules/.+/src/Entity/#',
      '#modules/.+/src/Plugin/Derivative#',
      '#modules/.+/src/Plugin/Field/FieldType#',
      '#modules/.+/src/Plugin/Field/FieldWidget#',
      '#modules/.+/src/Plugin/Field/FieldType#',
    ];

    $skipProcessing = FALSE;
    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $currentFile)) {
        // Skip processing.
        $skipProcessing = TRUE;
        break;
      }
    }

    if ($skipProcessing) {
      return;
    }

    $tokens = $phpcsFile->getTokens();
    $loopStackPtr = $stackPtr;
    $loopStackPtr = $phpcsFile->findNext(T_STRING, ($loopStackPtr + 1));

    // Check if t function call.
    if (
      $tokens[$loopStackPtr]['type'] === "T_STRING" &&
      $tokens[$loopStackPtr]['content'] === "t" &&
      $this->isTranslationFunction($phpcsFile, $loopStackPtr)
    ) {
      $openParenthesis = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $loopStackPtr);
      // Find the closing parenthesis of the function call.
      $closeParenthesis = $tokens[$openParenthesis]['parenthesis_closer'];
      // Find the first argument of the function call.
      $firstArgument = $phpcsFile->findNext(T_COMMA, $openParenthesis, $closeParenthesis);
      if (!$firstArgument) {
        $phpcsFile->addError($this->getErrorMessage(), $loopStackPtr, 'InvalidContext');
        return;
      }
      // Find the second argument of the function call.
      $scondArgument = $phpcsFile->findNext(T_COMMA, ($firstArgument + 1), $closeParenthesis);
      if (!$scondArgument) {
        $phpcsFile->addError($this->getErrorMessage(), $loopStackPtr, 'InvalidContext');
        return;
      }
      $openOptionsArray = $phpcsFile->findNext(T_OPEN_SHORT_ARRAY, $scondArgument);
      // Find the closing array bracket of the function call.
      $closeOptionsArray = $tokens[$openOptionsArray]['bracket_closer'];
      $contextKey = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, $openOptionsArray, $closeOptionsArray);
      if (!$contextKey || !in_array($tokens[$contextKey]['content'], ["'context'", 'context', '"context"'])) {
        $phpcsFile->addError($this->getErrorMessage(), $loopStackPtr, 'InvalidContext');
        return;
      }
      $contextValue = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, ($contextKey + 1), $closeOptionsArray);
      if (!$contextValue || !in_array($tokens[$contextValue]['content'], ["'_FRONTEND'", '_FRONTEND', '"_FRONTEND"'])) {
        $phpcsFile->addError($this->getErrorMessage(), $loopStackPtr, 'InvalidContext');
      }
    }

  }

  private function isTranslationFunction(File $phpcsFile, $stackPtr) {
    $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
    return $phpcsFile->getTokens()[$nextToken]['type'] === 'T_OPEN_PARENTHESIS';
  }

  private function getErrorMessage() {
    return "t function should use context _FRONTEND\nEx: t('Hello world', [], ['context' => '_FRONTEND']);\nIf you see that the translated string is not related to _FRONTEND context please ignore this warning and proceed to next ones whenever exist, finally use the commit command --no-verify option to bypass checks";
  }

}
