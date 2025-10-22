<?php declare(strict_types=1);

namespace App\Presenters;

use App\Model\Entity\Faction;
use App\Model\Modules\Faction\FactionFacade;
use Doctrine\ORM\EntityManagerInterface;
use Nette;

final class FactionPresenter extends BasePresenter
{
    public function __construct(
        EntityManagerInterface $em,
        private FactionFacade $factionFacade,
    ) {
        parent::__construct($em);
    }

    /**
     * URL: /faction?slug=noctis&season=123
     * - slug: volitelné, když chybí, vezme se default frakce z FactionFacade
     * - season: volitelné (int); když chybí/není platná, vezme se aktuální sezóna
     */
    public function renderDefault(?string $slug = null): void
    {
        $this->template->showRain = true;

        // 1) frakce
        $faction = $this->factionFacade->getFactionBySlugOrDefault($slug);
        if (!$faction) {
            $this->error('Faction not found', Nette\Http\IResponse::S404_NotFound);
        }

        // 2) sezóna (query param ?season=ID je volitelný)
        $seasonIdParam = $this->getParameter('season');
        $season = null;
        if ($seasonIdParam !== null && is_numeric($seasonIdParam) && (int)$seasonIdParam > 0) {
            // pokud máš ve FactionFacade metodu getSeasonById, použij ji; jinak klidně nech jen getCurrentSeason()
            if (method_exists($this->factionFacade, 'getSeasonById')) {
                /** @var object|null $tmp */
                $tmp = $this->factionFacade->getSeasonById((int)$seasonIdParam);
                $season = $tmp ?: null;
            }
        }
        if (!$season) {
            $season = $this->factionFacade->getCurrentSeason();
        }

        // 3) agregované statistiky frakce v sezóně
        // očekává: ['pointsTotal'=>int, 'cardsByRarity'=>['C'=>..,'U'=>..,'R'=>..,'E'=>..,'L'=>..]]
        $stats = $this->factionFacade->getSeasonStats(
            $faction->getId(),
            $season?->getId()
        );

        /*
        // 4) poslední příspěvky (zatím placeholder dle tvé FactionFacade)
        $last = $this->factionFacade->getLastContributions(
            $faction->getId(),
            $season?->getId(),
            20
        );
        */

        // 5) namapuj data pro šablonu do tvaru, který už používáš
        $cardsByRarity = (array)($stats['cardsByRarity'] ?? []);
        $f = [
            'points'    => (int)($stats['pointsTotal'] ?? 0),
            'common'    => (int)($cardsByRarity['C'] ?? 0),
            'uncommon'  => (int)($cardsByRarity['U'] ?? 0),
            'rare'      => (int)($cardsByRarity['R'] ?? 0),
            'epic'      => (int)($cardsByRarity['E'] ?? 0),
            'legendary' => (int)($cardsByRarity['L'] ?? 0),
        ];

        // 6) „seasonFaction“ objekt pro hlavičku v šabloně (název, slug, barva, začátek/konec, rank placeholder)
        $seasonFaction = (object)[
            'name'  => method_exists($faction, 'getName')  ? $faction->getName()  : ($faction->name ?? null),
            'slug'  => method_exists($faction, 'getSlug')  ? $faction->getSlug()  : ($faction->slug ?? null),
            'color' => method_exists($faction, 'getColor') ? $faction->getColor() : ($faction->color ?? null),
            'start' => $season?->{method_exists($season, 'getStartAt') ? 'getStartAt' : (method_exists($season, 'getStartsAt') ? 'getStartsAt' : null)}()
                        ?? null,
            'end'   => $season?->{method_exists($season, 'getEndAt') ? 'getEndAt' : (method_exists($season, 'getEndsAt') ? 'getEndsAt' : null)}()
                        ?? null,
            'rank'  => null, // můžeš naplnit později (finální umístění)
        ];

        $factions = $this->factionFacade->getMainFactions();
        
        // 7) předání do šablony
        $this->template->factions      = $factions;
        $this->template->faction       = $faction;
        $this->template->season        = $season;
        $this->template->f             = $f;               // pro tvoje rarity karty a body v záhlaví
        $this->template->seasonFaction = $seasonFaction;   // pro „brand“ v hlavičce sezóny
        //$this->template->last          = $last;            // tabulka posledních příspěvků apod.
    }
}
