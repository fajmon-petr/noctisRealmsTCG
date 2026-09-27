<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Rarity;
use App\Model\Modules\Faction\FactionFacade;
use App\Model\Modules\Season\SeasonFacade;
use Nette;

final class FactionPresenter extends BasePresenter
{
    public function __construct(
        private FactionFacade $factionFacade,
        private SeasonFacade $seasonFacade,
    ) {
        parent::__construct();
    }

    /**
     * URL: /faction?slug=noctis&season=123
     * - slug: když chybí/neexistuje, vezme se výchozí frakce
     * - season: když chybí/neexistuje, vezme se probíhající sezóna
     */
    public function renderDefault(?string $slug = null, ?int $season = null): void
    {
        $this->template->showRain = true;

        $faction = $this->factionFacade->getFactionBySlugOrDefault($slug);
        if (!$faction) {
            $this->error('Faction not found', Nette\Http\IResponse::S404_NotFound);
        }

        $seasonEntity = ($season !== null ? $this->seasonFacade->getById($season) : null)
            ?? $this->seasonFacade->getCurrentSeason();

        // agregované statistiky frakce v sezóně
        $stats = $this->factionFacade->getSeasonStats($faction->getId(), $seasonEntity?->getId());
        $cards = $stats['cardsByRarity'];

        $player = $this->getUser()->isLoggedIn()
            ? $this->playerFacade->findByUserId((int) $this->getUser()->getId())
            : null;

        $this->template->factions = $this->factionFacade->getMainFactions();
        $this->template->faction = $faction;
        $this->template->season = $seasonEntity;
        $this->template->seasonStats = $stats;
        $this->template->factionCards = [
            'common' => $cards[Rarity::COMMON],
            'uncommon' => $cards[Rarity::UNCOMMON],
            'rare' => $cards[Rarity::RARE],
            'epic' => $cards[Rarity::EPIC],
            'legendary' => $cards[Rarity::LEGENDARY],
        ];
        $this->template->achievementsUpcoming = $this->factionFacade->getFactionAchievements();
        $this->template->belongsToFaction = $player?->getFaction()?->getId() === $faction->getId();

        // TODO placeholdery – level frakce a cíle příspěvků zatím nemají datový model
        $this->template->xp = 0;
        $this->template->xpToNext = 100;
        $this->template->nextLevel = 2;
        $this->template->dustGoal = 1000;
        $this->template->dustProgress = 100;
        $this->template->cardsGoal = 1000;
        $this->template->cardsProgress = 100;
    }
}
