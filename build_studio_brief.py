from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from pathlib import Path

OUT=Path(r'E:\project\prepla\Brief_studio_videos_essentielles.docx')
BLUE='1F4D78'; NAVY='0B2545'; PALE='F4F6F9'
def font(r,sz=10.2,b=False,col='000000'):
    r.font.name='Calibri';r._element.rPr.rFonts.set(qn('w:ascii'),'Calibri');r._element.rPr.rFonts.set(qn('w:hAnsi'),'Calibri');r.font.size=Pt(sz);r.bold=b;r.font.color.rgb=RGBColor.from_string(col)
def shade(c,col):
    p=c._tc.get_or_add_tcPr();e=OxmlElement('w:shd');e.set(qn('w:fill'),col);p.append(e)
def text(c,x,b=False,col='000000',sz=9):
    c.text='';p=c.paragraphs[0];p.paragraph_format.space_after=Pt(0);p.paragraph_format.line_spacing=1.04;r=p.add_run(x);font(r,sz,b,col);c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
    pr=c._tc.get_or_add_tcPr();m=OxmlElement('w:tcMar')
    for side,v in [('top','100'),('start','120'),('bottom','100'),('end','120')]: e=OxmlElement('w:'+side);e.set(qn('w:w'),v);e.set(qn('w:type'),'dxa');m.append(e)
    pr.append(m)
d=Document();s=d.sections[0];s.top_margin=Inches(.7);s.bottom_margin=Inches(.65);s.left_margin=Inches(.7);s.right_margin=Inches(.7)
normal=d.styles['Normal'];normal.font.name='Calibri';normal._element.rPr.rFonts.set(qn('w:ascii'),'Calibri');normal._element.rPr.rFonts.set(qn('w:hAnsi'),'Calibri');normal.font.size=Pt(10.2);normal.paragraph_format.space_after=Pt(5)
for name,sz in [('Heading 1',15),('Heading 2',12)]:
    st=d.styles[name];st.font.name='Calibri';st._element.rPr.rFonts.set(qn('w:ascii'),'Calibri');st._element.rPr.rFonts.set(qn('w:hAnsi'),'Calibri');st.font.size=Pt(sz);st.font.bold=True;st.font.color.rgb=RGBColor.from_string(BLUE);st.paragraph_format.space_before=Pt(10);st.paragraph_format.space_after=Pt(5)
p=d.add_paragraph();p.paragraph_format.space_before=Pt(24);p.paragraph_format.space_after=Pt(4);r=p.add_run('Brief studio — vidéos essentielles');font(r,25,True,NAVY)
p=d.add_paragraph();p.paragraph_format.space_after=Pt(14);r=p.add_run('Objectif : lancer la communication avec 9 vidéos simples, claires et immédiatement réutilisables.');font(r,11,False,'4A5568')
d.add_heading('À retenir pour toutes les vidéos',1)
p=d.add_paragraph();r=p.add_run('Format : ');font(r,10.2,True,NAVY);r=p.add_run('vertical 9:16, sous-titres, 15 à 60 secondes, logo discret, un seul appel à l’action. Commencer par le problème ou le résultat dès les 3 premières secondes.');font(r)
table=d.add_table(rows=1,cols=5);table.style='Table Grid';table.alignment=WD_TABLE_ALIGNMENT.CENTER;table.autofit=False
widths=[1.1,1.55,.65,2.45,1.05]
heads=['Application','Vidéo à produire','Durée','Pourquoi cette vidéo','Action finale']
for c,h,w in zip(table.rows[0].cells,heads,widths):c.width=Inches(w);text(c,h,True,'FFFFFF');shade(c,BLUE)
rows=[
['Prépla','1. Du stress au plan de révision','60-75 s','C’est la vidéo de présentation : elle montre le choix d’examen, le test de niveau et le parcours. Elle fait comprendre que Prépla prépare précisément à un examen.','Faire le diagnostic'],
['Prépla','2. Simulation d’examen','45-60 s','Elle apporte la preuve : question, chrono, réponse, résultat et point à travailler.','Faire une simulation'],
['Prépla','3. Question d’examen du jour','20-30 s','C’est le format régulier pour les réseaux : une question, une réponse, une explication. Déclinable chaque semaine.','Tester mes connaissances'],
['ProcureGenius','1. Un devis créé en une phrase','45-60 s','La meilleure démonstration de l’assistant IA : demande -> devis prérempli -> validation humaine.','Créer mon devis'],
['ProcureGenius','2. Facture en retard, relance claire','35-45 s','Montre un problème quotidien de PME et une action simple, sans jargon.','Gérer mes impayés'],
['ProcureGenius','3. Trésorerie avant la surprise','45-60 s','Montre la valeur de pilotage : vision à 60 jours puis décision recommandée.','Voir mon cash-flow'],
['Job-Light / Guidy','1. Le CV dit quoi améliorer','45-60 s','La heatmap rend la promesse visible : score -> faiblesse -> correction -> amélioration.','Analyser mon CV'],
['Job-Light / Guidy','2. La lettre sans page blanche','45-60 s','Montre le wizard puis la lettre personnalisée. Très facile à comprendre pour un candidat.','Créer ma lettre'],
['Job-Light / Guidy','3. Réponse entretien STAR','30-45 s','Format court et utile : question -> réponse confuse -> structure STAR -> réponse améliorée.','Préparer mon entretien'],
]
for i,row in enumerate(rows):
    cells=table.add_row().cells
    for c,x,w in zip(cells,row,widths):c.width=Inches(w);text(c,x);shade(c,PALE) if i%2 else None
d.add_heading('Consigne de tournage',1)
for x in ['Filmer de vraies captures de l’application, avec des données de démonstration propres et lisibles.', 'Prépla : ton motivant et pédagogique. ProcureGenius : ton sobre, fiable, professionnel. Job-Light : ton premium, humain et encourageant.', 'Ne montrer que les fonctions qui fonctionnent déjà en production. Ne pas promettre un score, une économie ou un emploi garanti.', 'À partir de chaque vidéo, livrer aussi un extrait de 15 secondes pour WhatsApp Status et la publicité.']:
    p=d.add_paragraph(style='List Bullet');p.paragraph_format.space_after=Pt(2);r=p.add_run(x);font(r,9.8)
d.save(OUT);print(OUT)
