<?php

namespace Drupal\Tests\vactory_rate\ExistingSite;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\vactory_rate\Controller\VoteController;

/**
 * Our use of drupal/rate (and votingapi) through the vote API.
 *
 * The vactory_rate module injects rate's bot detector and reads
 * rate.settings; rate was pinned to 3.1.0 after two reverted upgrades. Run this test on a candidate
 * version before upgrading it.
 *
 * @group vactory_rate
 * @group dependencies
 */
class RateVoteApiTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = ['vactory_rate'];

  /**
   * Vote, read the results, undo the vote.
   */
  public function testVoteResultsUndo(): void {
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Rated page',
      'status' => 1,
      'moderation_state' => 'published',
    ]);
    $switcher = \Drupal::service('account_switcher');
    $voter = $this->createUser([
      'can vote entity',
      'Undo entity vote',
      'access content',
    ]);
    $switcher->switchTo($voter);
    try {
      $controller = \Drupal::classResolver(VoteController::class);
      $vote = json_decode($controller->vote('node', $node->id(), 'fivestar', 4)->getContent(), TRUE);
      $results = json_decode($controller->results('node', $node->id())->getContent(), TRUE);
      $undo = json_decode($controller->undoVote('node', $node->id())->getContent(), TRUE);
      $after = json_decode($controller->results('node', $node->id())->getContent(), TRUE);
    }
    finally {
      $switcher->switchBack();
    }
    // The vote goes through rate's bot detector and is stored by votingapi.
    $this->assertTrue($vote['status']);
    $this->assertEquals(4, $vote['fivestar']['vote_average']);
    $this->assertEquals(1, $vote['fivestar']['vote_count']);
    $this->assertTrue($results['hasVoted']);
    $this->assertEquals(4, $results['vote']);
    $this->assertTrue($undo['status']);
    $this->assertFalse($after['hasVoted']);
  }

}
