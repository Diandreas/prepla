/**
 * Les donnees de la page d'accueil. Elles viennent du serveur (LandingController) :
 * config/languages.php pour les examens, le catalogue d'essai, la video et les
 * tarifs lus chez Stripe. Ecrites ici en dur, elles annoncaient encore 8 examens
 * et un prix en euros.
 */
export interface Exam {
    slug: string;
    name: string;
    levels?: string[] | null;
}

export interface Language {
    slug: string;
    name: string;
    native_name: string;
    flag: string;
    exams: Exam[];
}

/** Un exercice ouvert a l'essai sans compte (ExercicesDEssai::carte). */
export interface ExerciceDEssai {
    id: number;
    titre: string;
    partie: string | null;
    niveau: string | null;
    competence: string | null;
    questions: number;
    officiel: boolean;
}

export interface EssaiLangue {
    slug: string;
    nom: string;
    examens: { slug: string; nom: string; exercices: ExerciceDEssai[] }[];
}

/** La video du haut de page (App\Support\VideoAccueil). */
export interface VideoAccueil {
    sources: Record<
        | 'boucle_640' | 'boucle_960' | 'paysage' | 'paysage_leger' | 'portrait' | 'portrait_leger'
        | 'affiche_640' | 'affiche_1280' | 'affiche_640_jpg' | 'affiche_1280_jpg' | 'affiche_portrait'
        | 'etape_1' | 'etape_2' | 'etape_3',
        string
    >;
    poids: Record<string, number>;
    duree: number;
    fin: number;
}

/** Un tarif tel que Stripe le debite (App\Services\Billing\TarifsAffiches). */
export interface Tarif {
    id: string;
    amount: number;
    currency: string;
    interval: string;
}
