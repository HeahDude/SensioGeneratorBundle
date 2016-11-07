<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Command\Style;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jules Pietri <jules@heahprod.com>
 */
class SensioGeneratorStyle extends SymfonyStyle
{
    const MAX_ATTEMPTS = 5;

    /**
     * @param string        $description
     * @param string[]|null $result
     * @param string[]      $errors
     */
    public function test($description, array $result, array &$errors)
    {
        $this->write($description);

        if ($result) {
            $this->writeln(' <fg=red>FAILED</>');
            array_merge($errors, $result);
        }

        $this->writeln(' <info>OK</info>');
    }

    /**
     * @param string[] $errors
     */
    public function summary(array $errors = array())
    {
        if (!$errors) {
            $this->success('Done! Now get to work :).');
        } else {
            $this->error(array(
                'The command was not able to configure everything automatically.',
                'You\'ll need to make the following changes manually.',
            ) + $errors);
        }
    }

    /**
     * @param string              $question
     * @param string[]            $suggestions
     * @param string|null         $default
     * @param callable|false|null $validator If false there is no validation, if null
     *                                       validation is based on the suggestions
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the answer in invalid
     */
    public function askWithCompletion($question, array $suggestions, $default = null, $validator = null)
    {
        $question = new Question($question, $default);
        $question
            ->setAutocompleterValues($suggestions)
            ->setMaxAttempts(self::MAX_ATTEMPTS)
        ;
        if (false !== $validator) {
            $question->setValidator($validator ?: function ($answer) use ($suggestions) {
                if (!in_array($answer, $suggestions, true)) {
                    throw new \InvalidArgumentException(sprintf('The given answer "%s" is not a valid choice. Choices are "%"', $answer, implode('", "', $suggestions)));
                }

                return $answer;
            });
        }

        return $this->askQuestion($question);
    }
}
