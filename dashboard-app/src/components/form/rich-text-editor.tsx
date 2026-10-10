import * as React from "react"
import {
  IconArrowBackUp,
  IconArrowForwardUp,
  IconBlockquote,
  IconBold,
  IconClearFormatting,
  IconH2,
  IconH3,
  IconItalic,
  IconLink,
  IconList,
  IconListNumbers,
  IconUnderline,
  type Icon,
} from "@tabler/icons-react"
import { Placeholder } from "@tiptap/extensions"
import { EditorContent, useEditor, useEditorState, type Editor } from "@tiptap/react"
import StarterKit from "@tiptap/starter-kit"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Separator } from "@/components/ui/separator"
import { Toggle } from "@/components/ui/toggle"
import { cn } from "@/lib/utils"

export interface EditorKayaProps {
  id?: string
  value: string
  onChange: (html: string) => void
  onBlur?: () => void
  label: string
  placeholder?: string
  invalid?: boolean
  describedBy?: string
}

function TombolFormat({
  ikon: Ikon,
  label,
  aktif,
  onClick,
  nonaktif,
}: {
  ikon: Icon
  label: string
  aktif?: boolean
  onClick: () => void
  nonaktif?: boolean
}) {
  return (
    <Toggle
      size="sm"
      pressed={!!aktif}
      onPressedChange={onClick}
      disabled={nonaktif}
      aria-label={label}
      title={label}
      className="size-8"
    >
      <Ikon className="size-4" />
    </Toggle>
  )
}

function PasangTautan({ editor }: { editor: Editor }) {
  const [buka, setBuka] = React.useState(false)
  const [url, setUrl] = React.useState("")
  const aktif = editor.isActive("link")

  const pasang = () => {
    const bersih = url.trim()
    if (!bersih) {
      editor.chain().focus().extendMarkRange("link").unsetLink().run()
    } else {
      const href = /^(https?:|mailto:|tel:|\/|#|[\w-]+\.html)/i.test(bersih) ? bersih : `https://${bersih}`
      editor.chain().focus().extendMarkRange("link").setLink({ href }).run()
    }
    setBuka(false)
  }

  return (
    <Popover
      open={buka}
      onOpenChange={(o) => {
        setBuka(o)
        if (o) setUrl(editor.getAttributes("link").href ?? "")
      }}
    >
      <PopoverTrigger asChild>
        <Toggle size="sm" pressed={aktif} aria-label="Tautan" title="Tautan" className="size-8">
          <IconLink className="size-4" />
        </Toggle>
      </PopoverTrigger>
      <PopoverContent align="start" className="w-80">
        <form
          className="grid gap-2"
          onSubmit={(e) => {
            e.preventDefault()
            pasang()
          }}
        >
          <label htmlFor="tautan-editor" className="text-sm font-medium">
            Alamat tautan
          </label>
          <Input
            id="tautan-editor"
            value={url}
            onChange={(e) => setUrl(e.target.value)}
            placeholder="https://… atau kontak.html#masukan"
            autoFocus
          />
          <div className="flex justify-end gap-2">
            {aktif && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => {
                  editor.chain().focus().extendMarkRange("link").unsetLink().run()
                  setBuka(false)
                }}
              >
                Lepas tautan
              </Button>
            )}
            <Button type="submit" size="sm">
              Pasang
            </Button>
          </div>
        </form>
      </PopoverContent>
    </Popover>
  )
}

export default function EditorKaya({
  id,
  value,
  onChange,
  onBlur,
  label,
  placeholder = "Tulis isi di sini…",
  invalid,
  describedBy,
}: EditorKayaProps) {
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: { levels: [2, 3] },
        code: false,
        codeBlock: false,
        link: {
          openOnClick: false,
          autolink: true,
          protocols: ["http", "https", "mailto", "tel"],
          HTMLAttributes: { rel: "noopener", target: null },
        },
      }),
      Placeholder.configure({ placeholder }),
    ],
    content: value || "",
    editorProps: {
      attributes: {
        ...(id ? { id } : {}),
        class: "prose-pintu min-h-64 px-4 py-3 outline-none",
        role: "textbox",
        "aria-multiline": "true",
        "aria-label": label,
        ...(describedBy ? { "aria-describedby": describedBy } : {}),
        ...(invalid ? { "aria-invalid": "true" } : {}),
      },
    },
    onUpdate: ({ editor: e }) => {
      if (!e.isDestroyed) onChange(e.isEmpty ? "" : e.getHTML())
    },
    onBlur: () => onBlur?.(),
  })

  // sinkronkan bila nilai berubah dari luar (mis. formulir di-reset)
  React.useEffect(() => {
    // editor bisa sudah dihancurkan (mis. saat React StrictMode memasang ulang komponen)
    if (!editor || editor.isDestroyed || editor.isFocused) return
    const sekarang = editor.isEmpty ? "" : editor.getHTML()
    if ((value || "") !== sekarang) editor.commands.setContent(value || "", { emitUpdate: false })
  }, [editor, value])

  const st = useEditorState({
    editor,
    selector: ({ editor: e }) => {
      if (!e || e.isDestroyed) return null
      return {
      bold: e?.isActive("bold") ?? false,
      italic: e?.isActive("italic") ?? false,
      underline: e?.isActive("underline") ?? false,
      h2: e?.isActive("heading", { level: 2 }) ?? false,
      h3: e?.isActive("heading", { level: 3 }) ?? false,
      ul: e?.isActive("bulletList") ?? false,
      ol: e?.isActive("orderedList") ?? false,
      quote: e?.isActive("blockquote") ?? false,
      undo: e?.can().undo() ?? false,
      redo: e?.can().redo() ?? false,
      }
    },
  })

  if (!editor || editor.isDestroyed) return null
  const c = () => editor.chain().focus()

  return (
    <div
      className={cn(
        "overflow-hidden rounded-md border bg-transparent shadow-xs transition-[color,box-shadow] focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 dark:bg-input/30",
        invalid && "border-destructive ring-destructive/20"
      )}
    >
      <div
        role="toolbar"
        aria-label={`Format ${label}`}
        className="flex flex-wrap items-center gap-0.5 border-b bg-muted/40 p-1"
      >
        <TombolFormat ikon={IconBold} label="Tebal" aktif={st?.bold} onClick={() => c().toggleBold().run()} />
        <TombolFormat ikon={IconItalic} label="Miring" aktif={st?.italic} onClick={() => c().toggleItalic().run()} />
        <TombolFormat ikon={IconUnderline} label="Garis bawah" aktif={st?.underline} onClick={() => c().toggleUnderline().run()} />
        <Separator orientation="vertical" className="mx-1 data-[orientation=vertical]:h-5" />
        <TombolFormat ikon={IconH2} label="Subjudul" aktif={st?.h2} onClick={() => c().toggleHeading({ level: 2 }).run()} />
        <TombolFormat ikon={IconH3} label="Subjudul kecil" aktif={st?.h3} onClick={() => c().toggleHeading({ level: 3 }).run()} />
        <TombolFormat ikon={IconList} label="Daftar berbutir" aktif={st?.ul} onClick={() => c().toggleBulletList().run()} />
        <TombolFormat ikon={IconListNumbers} label="Daftar bernomor" aktif={st?.ol} onClick={() => c().toggleOrderedList().run()} />
        <TombolFormat ikon={IconBlockquote} label="Kutipan" aktif={st?.quote} onClick={() => c().toggleBlockquote().run()} />
        <PasangTautan editor={editor} />
        <Separator orientation="vertical" className="mx-1 data-[orientation=vertical]:h-5" />
        <TombolFormat ikon={IconClearFormatting} label="Hapus format" onClick={() => c().unsetAllMarks().clearNodes().run()} />
        <div className="ml-auto flex">
          <TombolFormat ikon={IconArrowBackUp} label="Urungkan" nonaktif={!st?.undo} onClick={() => c().undo().run()} />
          <TombolFormat ikon={IconArrowForwardUp} label="Ulangi" nonaktif={!st?.redo} onClick={() => c().redo().run()} />
        </div>
      </div>
      <EditorContent editor={editor} />
    </div>
  )
}
