<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top">
		<h2><?= $this->data['attrs']['label_heading'] ?></h2>
	</rn:block>

	<rn:block id="resultListItem">
		<ul class="rn_List">
			<? $truncateSize = $this->data['attrs']['truncate_title_at']; ?>
			<? foreach($this->data['articles'] as $article): ?>
				<li class="rn_RelatedKnowledgeAdvanceAnswersItem">
					<span class="rn_Title">
						<a href="<?= $this->data['attrs']['answer_detail_url']. "/a_id/$article->answerId" ?>" title="<?= $article->title ?>">
							<?= strlen($article->title) > $truncateSize ? substr($article->title, 0, $truncateSize) . "..." : $article->title ?>
						</a>
					</span>
				</li>
			<? endforeach; ?>
		</ul>
	</rn:block>
</div>
