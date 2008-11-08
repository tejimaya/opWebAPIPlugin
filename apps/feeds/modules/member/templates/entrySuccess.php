<entry>
  <id><?php echo $member->getId() ?></id>
  <title><?php echo $member->getName() ?></title>
  <link><?php echo url_for('member/profile?id='.$member->getId(), true) ?></link>
  <content>
    <?php echo $member->getProfile('self_intro') ?>
  </content>
  <published><?php echo $member->getCreatedAt() ?></published>
  <updated><?php echo $member->getUpdatedAt() ?></updated>
</entry>
