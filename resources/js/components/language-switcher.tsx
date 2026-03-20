import * as React from "react"
import { useTranslation } from "react-i18next"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Globe } from "lucide-react"

export function LanguageSwitcher() {
  const { i18n } = useTranslation();

  const changeLanguage = (lng: string) => {
    i18n.changeLanguage(lng);
    // Optionnel: Sauvegarder la langue dans le profil utilisateur via une requête Inertia
  };

  const currentLanguage = i18n.language.split('-')[0];

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="h-9 w-9 rounded-full">
          <Globe className="h-4 w-4" />
          <span className="sr-only">Toggle language</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem onClick={() => changeLanguage('fr')} className={currentLanguage === 'fr' ? 'bg-accent' : ''}>
          Français
        </DropdownMenuItem>
        <DropdownMenuItem onClick={() => changeLanguage('en')} className={currentLanguage === 'en' ? 'bg-accent' : ''}>
          English
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
