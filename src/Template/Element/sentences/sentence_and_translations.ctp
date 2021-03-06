<?php
use App\Lib\LanguagesLib;
use App\Model\CurrentUser;

$this->Html->script('/js/directives/sentence-and-translations.dir.js', array('block' => 'scriptBottom'));

list($directTranslations, $indirectTranslations) = $translations;
$maxDisplayed = 5;
$showExtra = '';
$numExtra = count($directTranslations) + count($indirectTranslations) - $maxDisplayed;
$sentenceLink = $this->Html->link(
    '#'.$sentence->id,
    array(
        'controller' => 'sentences',
        'action' => 'show',
        $sentence->id
    )
);
$sentenceUrl = $this->Url->build(array(
    'controller' => 'sentences',
    'action' => 'show',
    $sentence->id
));
$notReliable = $sentence->correctness == -1;

$sentenceText = h($sentence->text);
if (isset($sentence->highlight)) {
    $highlight = $sentence->highlight;
    $sentenceText = $this->Search->highlightMatches($highlight, $sentenceText);
}

$username = $user ? $user->username : null;
$sentenceMenu = [
    'canEdit' => CurrentUser::canEditSentenceOfUser($username),
    'canRate' => CurrentUser::get('settings.users_collections_ratings'),
    'canAdopt' => CurrentUser::isTrusted() && !$user,
    'canDelete' => CurrentUser::canRemoveSentence($sentence->id, null, $username),
    'canLink' => CurrentUser::isTrusted(),
];

$directTranslationsJSON = $this->Sentences->translationsForAngular($directTranslations);
$indirectTranslationsJSON = $this->Sentences->translationsForAngular($indirectTranslations);
?>
<div ng-cloak
     sentence-and-translations
     ng-init="vm.initTranslations(<?= $directTranslationsJSON ?>, <?= $indirectTranslationsJSON ?>)"
     class="sentence-and-translations md-whiteframe-1dp">
    <div layout="column">
        <div layout="row" class="header">
            <md-subheader flex class="ellipsis">
                <?php
                if ($user) {
                    $userLink = $this->Html->link(
                        $user->username,
                        array(
                            'controller' => 'user',
                            'action' => 'profile',
                            $user->username
                        )
                    );
                    echo format(
                        __('Sentence {number} — belongs to {username}'),
                        array(
                            'number' => $sentenceLink,
                            'username' => $userLink
                        )
                    );
                } else {
                    echo format(
                        __('Sentence {number}'),
                        array(
                            'number' => $sentenceLink
                        )
                    );
                }            
                ?>
            </md-subheader>

            <?php
            if (CurrentUser::isMember()) {
                echo $this->element('sentences/sentence_menu', [
                    'sentence' => $sentence,
                    'menu' => $sentenceMenu
                ]);
            }
            ?>
        </div>

        <div class="sentence <?= $notReliable ? 'not-reliable' : '' ?>"
             layout="row" layout-align="start center">
            <div class="lang">
                <?= $this->Languages->icon($sentence->lang) ?>
            </div>
            <div class="text" flex
                 dir="<?= LanguagesLib::getLanguageDirection($sentence->lang) ?>">
                <?= $sentenceText ?>
            </div>
            <?php if ($notReliable) { ?>
                <md-icon class="md-warn">warning</md-icon>
                <md-tooltip md-direction="top">
                    <?= __('This sentence is not reliable.') ?>
                </md-tooltip>
            <?php } ?>
            
            <?= $this->element('sentence_buttons/audio', ['sentence' => $sentence]); ?>

            <md-button class="md-icon-button" href="<?= $sentenceUrl ?>">
                <md-icon>info</md-icon>
            </md-button>
        </div>
    </div>

    <?php
    if (CurrentUser::isMember()) { 
        echo $this->element('sentences/translation_form', [
            'sentenceId' => $sentence->id
        ]);
    }
    ?>

    <div layout="column" class="direct translations" ng-if="!vm.isTranslationFormVisible && vm.directTranslations.length > 0">
        <md-divider></md-divider>
        <md-subheader><?= __('Translations') ?></md-subheader>

        <?php
        echo $this->element('sentences/translation', [
            'translations' => 'vm.directTranslations'
        ]);
        ?>
    </div>
    
    <div layout="column" <?= $showExtra ?> class="indirect translations" ng-if="!vm.isTranslationFormVisible && vm.indirectTranslations.length > 0"
            ng-init="vm.initIndirectTranslations(<?= $this->Sentences->translationsForAngular($indirectTranslations) ?>)">
        <md-subheader><?= __('Translations of translations') ?></md-subheader>
        
        <?php
        echo $this->element('sentences/translation', [
            'translations' => 'vm.indirectTranslations'
        ]);
        ?>
    </div>


    <?php if ($numExtra > 1) { ?>
        <div layout="column" ng-if="!vm.isTranslationFormVisible">
            <md-button ng-click="vm.expandOrCollapse()">
                <md-icon>{{vm.expandableIcon}}</md-icon>
                <span ng-if="!vm.isExpanded">
                    <?php
                    echo format(__n(
                        'Show 1 more translation',
                        'Show {number} more translations',
                        $numExtra,
                        true
                    ), array('number' => $numExtra))
                    ?>
                </span>
                <span ng-if="vm.isExpanded">
                    <?php echo __('Fewer translations') ?>
                </span>
            </md-button>
        </div>
    <?php } ?>
</div>
